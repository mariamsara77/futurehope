<?php

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    public function up(): void
    {
        // Avatar ownership is now User -> MediaLibrary. Preserve any existing
        // profile avatar media by re-pointing it to the related user.
        $profileType = Relations::getMorphAlias(Profile::class);
        $userType = Relations::getMorphAlias(User::class);

        Media::query()
            ->where('model_type', $profileType)
            ->where('collection_name', 'avatar')
            ->get()
            ->each(function (Media $media) use ($userType): void {
                $profile = Profile::query()->find($media->model_id);

                if (!$profile?->user_id) {
                    return;
                }

                $user = User::query()->find($profile->user_id);

                if (!$user) {
                    return;
                }

                $existing = Media::query()
                    ->where('model_type', $userType)
                    ->where('model_id', $user->id)
                    ->where('collection_name', 'avatar')
                    ->first();

                if ($existing && $existing->id !== $media->id) {
                    $media->delete();
                    return;
                }

                $media->forceFill([
                    'model_type' => $userType,
                    'model_id' => $user->id,
                ])->save();
            });
    }

    public function down(): void
    {
        $profileType = Relations::getMorphAlias(Profile::class);
        $userType = Relations::getMorphAlias(User::class);

        Media::query()
            ->where('model_type', $userType)
            ->where('collection_name', 'avatar')
            ->get()
            ->each(function (Media $media) use ($profileType): void {
                $user = User::query()->find($media->model_id);

                if (!$user || !$user->profile) {
                    return;
                }

                $media->forceFill([
                    'model_type' => $profileType,
                    'model_id' => $user->profile->id,
                ])->save();
            });
    }
};
