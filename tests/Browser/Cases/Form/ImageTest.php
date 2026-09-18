<?php

namespace Tests\Browser\Cases\Form;

use Laravel\Dusk\Browser;
use Tests\TestCase;

/**
 * 图片上传测试.
 *
 * @group form:image
 */
class ImageTest extends TestCase
{
    public function testImageUploadPlaceholder()
    {
        // 图片上传 E2E 用例待补全；避免无断言的 browse() 被 PHPUnit 标记为 risky。
        $this->markTestSkipped('图片上传浏览器测试尚未实现');
    }
}
