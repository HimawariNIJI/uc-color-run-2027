<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Support\IdentityPhoto;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationIdentityPhotoController extends Controller
{
    /**
     * Show a registration's identity photo (KTP, or Kartu Keluarga for a Family Bundle) to a
     * signed-in admin. The route sits behind auth:admin and the photo has no public URL, so this
     * is the only way to see it.
     */
    public function __invoke(Registration $registration): StreamedResponse
    {
        $path = $registration->identityPhotoPath();

        abort_unless(IdentityPhoto::exists($path), 404);

        return IdentityPhoto::response($path, $registration->identityDocument()->value.'-'.$registration->code);
    }
}
