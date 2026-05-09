<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AvatarImageService
{
    private const Size = 512;

    private const Quality = 85;

    public function store(UploadedFile $avatar): string
    {
        $source = imagecreatefromstring($avatar->getContent());

        if ($source === false) {
            throw new RuntimeException('Não foi possível processar a imagem do avatar.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropSize = min($sourceWidth, $sourceHeight);
        $sourceX = (int) floor(($sourceWidth - $cropSize) / 2);
        $sourceY = (int) floor(($sourceHeight - $cropSize) / 2);

        $avatarImage = imagecreatetruecolor(self::Size, self::Size);

        if ($avatarImage === false) {
            imagedestroy($source);

            throw new RuntimeException('Não foi possível criar a imagem do avatar.');
        }

        imagecopyresampled(
            $avatarImage,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            self::Size,
            self::Size,
            $cropSize,
            $cropSize,
        );

        ob_start();
        imagejpeg($avatarImage, null, self::Quality);
        $contents = ob_get_clean();

        imagedestroy($avatarImage);
        imagedestroy($source);

        if ($contents === false) {
            throw new RuntimeException('Não foi possível salvar a imagem do avatar.');
        }

        $path = 'avatars/'.Str::random(40).'.jpg';

        Storage::disk('public')->put($path, $contents);

        return $path;
    }
}
