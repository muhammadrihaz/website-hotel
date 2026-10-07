<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConfirmationDialogTest extends TestCase
{
    public function test_all_livewire_confirmations_use_the_custom_dialog(): void
    {
        $customConfirmations = 0;

        foreach (File::allFiles(resource_path('views/livewire')) as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString('wire:confirm', $contents, $file->getRelativePathname());
            $customConfirmations += substr_count($contents, 'data-confirm=');
        }

        $this->assertSame(9, $customConfirmations);
    }

    public function test_custom_confirmation_dialog_has_accessible_controls(): void
    {
        $html = Blade::render('<x-confirmation-dialog />');

        $this->assertStringContainsString('data-confirm-dialog', $html);
        $this->assertStringContainsString('aria-labelledby="confirmation-dialog-title"', $html);
        $this->assertStringContainsString('aria-describedby="confirmation-dialog-message"', $html);
        $this->assertStringContainsString('data-confirm-dialog-cancel', $html);
        $this->assertStringContainsString('data-confirm-dialog-accept', $html);
    }
}
