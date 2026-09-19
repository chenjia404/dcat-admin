<?php

namespace Dcat\Admin\Console;

use Dcat\Admin\Support\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MinifyCommand extends Command
{
    const ALL = 'all';
    const DEFAULT = 'default';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:minify {name} 
        {--color= : Theme color code} 
        {--publish : Publish assets files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Minify the CSS and JS';

    /**
     * @var array
     */
    protected $colors = [
        self::DEFAULT => '',
        'blue'        => '#6d8be6',
        'blue-light'  => '#62a8ea',
        'green'       => '#4e9876',
    ];

    /**
     * @var string
     */
    protected $packagePath;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->packagePath = realpath(__DIR__.'/../..');
        $this->files = $this->laravel['files'];

        $name = $this->argument('name');

        if ($name === static::ALL) {
            // 编译所有内置主题色
            return $this->compileAllColors();
        }

        $publish = $this->option('publish');
        $color = $this->getColor($name);

        $this->backupFiles();
        $this->replaceFiles($name, $color);

        try {
            $this->npmInstall();

            $this->info("[$name][$color] pnpm run production...");

            // 编译
            $this->runProcess("cd {$this->packagePath} && pnpm run production", 1800);

            if ($publish) {
                $this->publishAssets();
            }
        } finally {
            // 重置文件
            $this->resetFiles();
        }
    }

    /**
     * 编译所有内置主题.
     */
    protected function compileAllColors()
    {
        foreach ($this->colors as $name => $_) {
            $this->call('admin:minify', ['name' => $name]);
        }
    }

    /**
     * 发布静态资源.
     */
    protected function publishAssets()
    {
        $options = ['--provider' => 'Dcat\Admin\AdminServiceProvider', '--force' => true, '--tag' => 'dcat-admin-assets'];

        $this->call('vendor:publish', $options);
    }

    /**
     * 替换文件.
     *
     * @param $name
     * @param $color
     */
    protected function replaceFiles($name, $color)
    {
        if ($name === static::DEFAULT) {
            return;
        }

        $viteFile = $this->getViteConfigFile();
        $contents = str_replace('let theme = null', "let theme = '{$name}'", $this->files->get($viteFile));
        $this->files->put($viteFile, $contents);

        $colorFile = $this->getColorFile();
        $this->files->put($colorFile, "\$primary: $color;");
    }

    /**
     * 备份文件.
     */
    protected function backupFiles()
    {
        if (! is_file($this->getViteConfigBakFile())) {
            $this->files->copy($this->getViteConfigFile(), $this->getViteConfigBakFile());
        } else {
            $this->files->delete($this->getViteConfigFile());
            $this->files->copy($this->getViteConfigBakFile(), $this->getViteConfigFile());
        }

        if (! is_file($this->getColorBakFile())) {
            $this->files->copy($this->getColorFile(), $this->getColorBakFile());
        }
    }

    /**
     * 重置文件.
     */
    protected function resetFiles()
    {
        $viteFile = $this->getViteConfigFile();
        $viteBakFile = $this->getViteConfigBakFile();

        if (is_file($viteBakFile)) {
            $this->files->delete($viteFile);
            $this->files->copy($viteBakFile, $viteFile);
            $this->files->delete($viteBakFile);
        }

        $colorFile = $this->getColorFile();
        $colorBakFile = $this->getColorBakFile();

        if (is_file($colorBakFile)) {
            $this->files->delete($colorFile);
            $this->files->copy($colorBakFile, $colorFile);
            $this->files->delete($colorBakFile);
        }
    }

    /**
     * @return string
     */
    protected function getViteConfigFile()
    {
        return $this->packagePath.'/vite.config.js';
    }

    /**
     * @return mixed
     */
    protected function getViteConfigBakFile()
    {
        return str_replace('.js', '.bak.js', $this->getViteConfigFile());
    }

    /**
     * @return string
     */
    protected function getColorFile()
    {
        return $this->packagePath.'/resources/assets/dcat/sass/theme/_primary.scss';
    }

    /**
     * @return mixed
     */
    protected function getColorBakFile()
    {
        return str_replace('.scss', '.bak.scss', $this->getColorFile());
    }

    /**
     * 安装依赖.
     */
    protected function npmInstall()
    {
        if (is_dir($this->packagePath.'/node_modules')) {
            return;
        }

        $this->info('pnpm install...');

        $this->runProcess("cd {$this->packagePath} && corepack enable && pnpm install");
    }

    /**
     * 获取颜色.
     *
     * @param  string  $name
     * @return string
     */
    protected function getColor($name)
    {
        if ($name === static::DEFAULT) {
            return '';
        }

        INPUT_COLOR:

        $color = $this->option('color');

        if (! $color && isset($this->colors[$name])) {
            return $this->colors[$name];
        }

        if (! $color) {
            $color = $this->formatColor($this->ask('Please enter a color code(hex)'));
        }

        if (! $color) {
            goto INPUT_COLOR;
        }

        return $this->formatColor($color);
    }

    /**
     * @param  string  $color
     * @return string
     */
    protected function formatColor($color)
    {
        if ($color && ! Str::startsWith($color, '#')) {
            $color = "#$color";
        }

        return $color;
    }

    /**
     * 执行命令.
     *
     * @param  string  $command
     * @param  int  $timeout
     */
    protected function runProcess($command, $timeout = 1800)
    {
        $process = Helper::process($command, $timeout);

        $process->run(function ($type, $data) {
            if ($type === Process::ERR) {
                $this->warn($data);
            } else {
                $this->info($data);
            }
        });
    }
}
