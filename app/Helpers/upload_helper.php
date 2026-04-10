<?php
function upload_image(array $file, string $dir = 'products', int $maxW = 800, int $maxH = 800): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== 0) return null;
    $mime = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg','image/png','image/webp','image/gif','image/jpg'];
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 10 * 1024 * 1024) return null;

    $filename = 'img_' . uniqid() . '.jpg';
    $destDir  = dirname(__DIR__, 2) . "/public/uploads/{$dir}/";
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $dest = $destDir . $filename;

    // Try GD resize
    try {
        $src = match(true) {
            in_array($mime,['image/jpeg','image/jpg']) => imagecreatefromjpeg($file['tmp_name']),
            $mime==='image/png'  => imagecreatefrompng($file['tmp_name']),
            $mime==='image/webp' => imagecreatefromwebp($file['tmp_name']),
            $mime==='image/gif'  => imagecreatefromgif($file['tmp_name']),
            default => null
        };
        if ($src) {
            $ow=imagesx($src); $oh=imagesy($src);
            $r=min($maxW/$ow,$maxH/$oh,1);
            $nw=(int)round($ow*$r); $nh=(int)round($oh*$r);
            $dst=imagecreatetruecolor($nw,$nh);
            $white=imagecolorallocate($dst,255,255,255);
            imagefilledrectangle($dst,0,0,$nw,$nh,$white);
            imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$ow,$oh);
            imagejpeg($dst,$dest,85);
            imagedestroy($src); imagedestroy($dst);
        }
    } catch (Throwable $e) {}

    // Fallback: direct copy
    if (!file_exists($dest)) copy($file['tmp_name'], $dest);

    // Return ONLY filename — path handled in frontend
    return file_exists($dest) ? $filename : null;
}
