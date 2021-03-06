<?php

namespace ChingShop\Listeners;

use Carbon\Carbon;
use ChingShop\Events\NewImageEvent;
use ChingShop\Image\Image;
use ChingShop\Image\Imagick\ImagePreProcessor;
use ChingShop\Image\Imagick\ImagickContract;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use SplFileObject;
use SplTempFileObject;

/**
 * Class NewImageListener
 * Optimise images and upload to S3.
 */
class NewImageListener implements ShouldQueue
{
    /** @var ImagePreProcessor */
    private $imagePreProcessor;

    /** @var Filesystem|FilesystemAdapter */
    private $publicFilesystem;

    /** @var Config */
    private $config;

    /**
     * @param ImagePreProcessor $imagePreProcessor
     * @param Filesystem        $publicFilesystem
     * @param Config            $config
     */
    public function __construct(
        ImagePreProcessor $imagePreProcessor,
        Filesystem $publicFilesystem,
        Config $config
    ) {
        $this->imagePreProcessor = $imagePreProcessor;
        $this->publicFilesystem = $publicFilesystem;
        $this->config = $config;
    }

    /**
     * @param NewImageEvent $event
     */
    public function handle(NewImageEvent $event)
    {
        if (!$this->storageImageFile($event->image())->isFile()) {
            $event->image()->setAttribute('filename', '');
            $event->image()->save();

            return;
        }

        $this->transferImageFiles($event);
        $this->deleteLocalImageFile($event);
        $this->updateImageResource($event);
    }

    /**
     * @param NewImageEvent $event
     */
    private function transferImageFiles(NewImageEvent $event)
    {
        $preProcessed = $this->imagePreProcessor->preProcess($event->image());
        /** @var ImagickContract $imagick */
        foreach ($preProcessed as $imagick) {
            $this->publicFilesystem->getDriver()->put(
                "image/{$imagick->getFilename()}",
                $imagick->getImageBlob(),
                self::fileConfig()
            );
        }
    }

    /**
     * @param NewImageEvent $event
     */
    private function deleteLocalImageFile(NewImageEvent $event)
    {
        $file = $this->storageImageFile($event->image());
        if ($file->isFile()) {
            unlink($file->getRealPath());
        }
        unset($file);
    }

    /**
     * @param NewImageEvent $event
     */
    private function updateImageResource(NewImageEvent $event)
    {
        $event->image()->setAttribute(
            'url',
            "{$this->filesystemEndpoint()}/image/{$event->image()->filename()}"
        );
        $event->image()->setAttribute('filename', '');
        $event->image()->save();
    }

    /**
     * @param Image $imageResource
     *
     * @return SplFileObject
     */
    private function storageImageFile(Image $imageResource): SplFileObject
    {
        try {
            return new SplFileObject($imageResource->storageLocation());
        } catch (\RuntimeException $e) {
            return new SplTempFileObject();
        }
    }

    /**
     * @return string
     */
    private function filesystemEndpoint(): string
    {
        return rtrim(
            secure_url(
                $this->config->get(
                    sprintf(
                        'filesystems.disks.%s.public',
                        $this->config->get('filesystems.default')
                    )
                )
            ),
            '/'
        );
    }

    /**
     * @return array
     */
    private static function fileConfig(): array
    {
        return [
            'visibility'   => Filesystem::VISIBILITY_PUBLIC,
            'CacheControl' => 'max-age=315360000, public',
            'Expires'      => Carbon::maxValue()->format(DATE_RFC850),
        ];
    }
}
