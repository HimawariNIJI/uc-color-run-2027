<?php

namespace App\Support;

use App\Enums\IdentityDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Photos of the payer's identity document: their KTP, or the family's Kartu Keluarga for a Family
 * Bundle (see IdentityDocument). Files live on the private "identity_photos" disk in a folder per
 * document ("ktp" or "kk"), under a random name whose extension comes from the file's content; the
 * registration only stores the path. Nothing here logs or reads the image itself.
 */
class IdentityPhoto
{
    public const DISK = 'identity_photos';

    /**
     * Largest accepted photo in kilobytes (5 MB).
     */
    public const MAX_KILOBYTES = 5 * 1024;

    /**
     * Accepted types, checked against the file's content and against its name.
     *
     * @var list<string>
     */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * MIME types for the browser's file picker and the preview script.
     *
     * @var list<string>
     */
    public const MIME_TYPES = ['image/jpeg', 'image/png'];

    /**
     * A real JPG or PNG image (the "mimes" rule reads the file's content), at most 5 MB and large
     * enough to read. Optional when the registration already has a photo.
     *
     * @return list<string>
     */
    public static function rules(bool $required): array
    {
        $types = implode(',', self::EXTENSIONS);

        return [
            'bail',
            $required ? 'required' : 'nullable',
            'file',
            "mimes:{$types}",
            "extensions:{$types}",
            'max:'.self::MAX_KILOBYTES,
            'dimensions:min_width=320,min_height=200',
        ];
    }

    /**
     * Validation messages for the document's form field.
     *
     * @return array<string, string>
     */
    public static function messages(IdentityDocument $document): array
    {
        $field = $document->field();
        $label = $document->label();
        $notAnImage = "{$label} harus berupa gambar JPG, JPEG, atau PNG.";

        return [
            "{$field}.required" => "{$label} wajib diunggah.",
            "{$field}.uploaded" => "{$label} gagal diunggah. Pastikan ukurannya tidak lebih dari 5 MB, lalu pilih ulang fotonya.",
            "{$field}.file" => $notAnImage,
            "{$field}.mimes" => $notAnImage,
            "{$field}.extensions" => $notAnImage,
            "{$field}.max" => "Ukuran {$label} maksimal 5 MB.",
            "{$field}.dimensions" => "{$label} tidak dapat dibaca atau terlalu kecil. Gunakan {$document->noun()} yang jelas, minimal 320 × 200 piksel.",
        ];
    }

    /**
     * Save an uploaded photo and return its path on the private disk. When the disk cannot write
     * it, the failure is reported and the participant is sent back to the form with an error.
     *
     * @throws ValidationException
     */
    public static function store(UploadedFile $photo, IdentityDocument $document): string
    {
        try {
            return $photo->store($document->value, self::DISK) ?: throw new RuntimeException('The identity photo could not be saved.');
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $document->field() => "{$document->label()} gagal disimpan. Silakan pilih ulang fotonya lalu kirim kembali.",
            ]);
        }
    }

    /**
     * Remove a photo that is no longer used. A failure is reported instead of thrown, so cleaning
     * up never hides the error that caused it.
     */
    public static function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        rescue(fn () => Storage::disk(self::DISK)->delete($path));
    }

    public static function exists(?string $path): bool
    {
        return filled($path) && Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Show the photo in the browser. It is never cached and never read as anything but an image.
     */
    public static function response(string $path, string $name): StreamedResponse
    {
        return Storage::disk(self::DISK)->response($path, $name.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
