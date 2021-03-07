<?php
declare(strict_types=1);

namespace esp\weiPay\service;
use esp\weiPay\ApiV3Base;


class Media extends ApiV3Base
{
    /**
     * @param string $filePath
     * @return array|string
     *
     * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter2_1_1.shtml
     */
    public function upload(string $filePath)
    {
        if (!is_readable($filePath)) return "要上传的文件({$filePath})不存在";
        $fileSize = \filesize($filePath);
        $mimeType = finfo_file(finfo_open(FILEINFO_EXTENSION), $filePath);
        $ext = explode('/', $mimeType)[0];
        if ($ext === '???') {
            $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
            $ext = explode('/', $mimeType);
            $ext = $ext[1];
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp'])) {
            if ($fileSize > 2 * 1024 * 1024) return "上传到微信的图片文件不能超过2M";
            return $this->uploadWx('/v3/merchant/media/upload', $filePath);

        } else if (in_array($ext, ['avi', 'wmv', 'mpeg', 'mp4', 'mov', 'mkv', 'flv', 'f4v', 'm4v', 'rmvb'])) {
            if ($fileSize > 5 * 1024 * 1024) return "上传到微信的视频文件不能超过5M";
            return $this->uploadWx('/v3/merchant/media/video_upload', $filePath);
        }

        return "不支持的文件类型：{$ext}";
    }

    /**
     * @param string $api
     * @param string $filePath
     * @return mixed|null
     */
    private function uploadWx(string $api, string $filePath)
    {
        $basename = \basename($filePath);
        $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);

        $meta = json_encode(['filename' => $basename, 'sha256' => hash_file('sha256', $filePath)]);
        $boundary = sha1(uniqid('', true));

        $option = [];
        $option['type'] = 'upload';
        $option['headers'] = [];
        $option['headers']['Authorization'] = $this->sign('POST', $api, $meta);
        $option['headers']['Content-Type'] = "multipart/form-data; boundary={$boundary}";

        $body = '';
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"meta\";\r\n";
        $body .= "Content-Type: application/json;\r\n";
        $body .= "\r\n";
        $body .= "{$meta}\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$basename}\";\r\n";
        $body .= "Content-Type: {$mimeType};\r\n";
        $body .= "\r\n";
        $body .= fread(fopen($filePath, "rb"), filesize($filePath)) . "\r\n";
        $body .= "--{$boundary}--";

        return $this->requestWx($option, $api, $body);
    }


}