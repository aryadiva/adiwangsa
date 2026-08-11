<?php

namespace App\Filament\Client\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @property Form $form
 */
class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Change Password';

    protected static ?string $title = 'Change Password';

    protected static string $view = 'filament.client.change-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label('Current Password')
                    ->password()
                    ->required()
                    ->autocomplete('current-password'),
                TextInput::make('new_password')
                    ->label('New Password')
                    ->password()
                    ->minLength(8)
                    ->required()
                    ->autocomplete('new-password'),
                TextInput::make('new_password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->same('new_password')
                    ->required()
                    ->autocomplete('new-password'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'The provided password does not match your current password.',
            ]);
        }

        $user->update([
            'password' => $data['new_password'],
            'must_change_password' => false,
        ]);

        Notification::make()
            ->title('Password updated.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl());
    }
}
