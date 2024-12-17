<?php

use Filament\Actions;
use Filament\Pages\Actions\DeleteAction;
use Filament\Pages\Actions\ForceDeleteAction;

use Filament\Pages\Actions\RestoreAction;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

use Visualbuilder\EmailTemplates\Models\EmailTemplate;
use Visualbuilder\EmailTemplates\Resources\EmailTemplateResource;
use Visualbuilder\EmailTemplates\Resources\EmailTemplateResource\Pages\CreateEmailTemplate;
use Visualbuilder\EmailTemplates\Resources\EmailTemplateResource\Pages\EditEmailTemplate;
use Visualbuilder\EmailTemplates\Resources\EmailTemplateResource\Pages\ListEmailTemplates;

// listing tests
it('can access email template list page', function () {
    get(EmailTemplateResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list email templates', function () {
    $emailTemplates = EmailTemplate::factory()->count(10)->create();

    livewire(ListEmailTemplates::class)
        ->assertCanSeeTableRecords($emailTemplates);
});

// create tests
it('can access email template create page', function () {
    $test =get(EmailTemplateResource::getUrl('create'));

    $test->assertSuccessful();
});

it('can create email template', function () {
    $newData = EmailTemplate::factory()->make();

    $storedData = livewire(CreateEmailTemplate::class)
        ->fillForm([
            'key' => $newData->key,
            'language' => $newData->language,
            'view' => $newData->view,
            'cc' => $newData->cc,
            'bcc' => $newData->bcc,
            //'from' => $newData->from,
            'name' => $newData->name,
            'preheader' => $newData->preheader,
            'subject' => $newData->subject,
            'title' => $newData->title,
            'content' => $newData->content,
            'deleted_at' => $newData->deleted_at,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(EmailTemplate::class, [
        'key' => $storedData->data['key'],
        'language' => $storedData->data['language'],
        'view' => $storedData->data['view'],
        'cc' => $storedData->data['cc'],
        'bcc' => $storedData->data['bcc'],
       //'from' => $storedData->data['from'],
        'name' => $storedData->data['name'],
        'preheader' => $storedData->data['preheader'],
        'subject' => $storedData->data['subject'],
        'title' => $storedData->data['title'],
        'content' => $storedData->data['content'],
        'deleted_at' => $storedData->data['deleted_at'],
    ]);
});

// edit tests
it('can access email template edit page', function () {
    get(EmailTemplateResource::getUrl('edit', [
        'record' => EmailTemplate::factory()->create(),
    ]))->assertSuccessful();
});

it('can update email an email template', function () {
    $data = EmailTemplate::factory()->create();
    $newData = EmailTemplate::factory()->make();

    $updatedData = livewire(EditEmailTemplate::class, [
        'record' => $data->getRouteKey(),
    ])
        ->fillForm([
            'language' => $newData->language,
            'view' => $newData->view,
            'cc' => $newData->cc,
            'bcc' => $newData->bcc,
            'from.email' => $newData->from['email'],
            'from.name' => $newData->from['name'],
            'name' => $newData->name,
            'preheader' => $newData->preheader,
            'subject' => $newData->subject,
            'title' => $newData->title,
            'content' => $newData->content,
            'deleted_at' => $newData->deleted_at,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(EmailTemplate::class, [
        'key' => $updatedData->data['key'],
        'language' => $updatedData->data['language'],
        'view' => $updatedData->data['view'],
        'cc' => $updatedData->data['cc'],
        'bcc' => $updatedData->data['bcc'],
        'name' => $updatedData->data['name'],
        'preheader' => $updatedData->data['preheader'],
        'subject' => $updatedData->data['subject'],
        'title' => $updatedData->data['title'],
        'content' => $updatedData->data['content'],
        'deleted_at' => $updatedData->data['deleted_at'],
    ]);
});

// delete and restore tests
it('can delete email template', function () {
    $emailTemplate = EmailTemplate::factory()->create();

    livewire(EditEmailTemplate::class, [
        'record' => $emailTemplate->getRouteKey(),
    ])->callAction(DeleteAction::class);

    $this->assertSoftDeleted($emailTemplate);
});

it('can restore email template', function () {
    $emailTemplate = EmailTemplate::factory()->create();

    livewire(EditEmailTemplate::class, [
        'record' => $emailTemplate->getRouteKey(),
    ])->callAction(DeleteAction::class);

    livewire(EditEmailTemplate::class, [
        'record' => $emailTemplate->getRouteKey(),
    ])->callAction(RestoreAction::class);

    $this->assertDatabaseHas(EmailTemplate::class, [
        'id' => $emailTemplate->getRouteKey(),
        'deleted_at' => null,
    ]);
});

it('can force delete email template', function () {
    $emailTemplate = EmailTemplate::factory()->create();

    livewire(EditEmailTemplate::class, [
        'record' => $emailTemplate->getRouteKey(),
    ])->callAction(DeleteAction::class);

    livewire(EditEmailTemplate::class, [
        'record' => $emailTemplate->getRouteKey(),
    ])->callAction(ForceDeleteAction::class);

    $this->assertModelMissing($emailTemplate);
});

// preview tests
it('can preview email template', function () {
    $emailTemplate = EmailTemplate::factory()->create();
    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailTemplate->getRouteKey(),
    ])->callAction(Actions\ViewAction::class)
    ->assertSuccessful();
});

it('can preview user welcome email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-welcome',
            'name' => 'User Welcome Email',
            'title' => 'Welcome to ##config.app.name##',

            'subject' => 'Welcome to ##config.app.name##',
            'preheader' => 'Lets get you started',
            'content' => "<p>Dear ##user.name##,</p>
                            <p>Thanks for registering with ##config.app.name##.</p>
                            <p>If you need any assistance please contact our customer services team ##config.email-templates.customer-services.email## who will be happy to help.</p>
                            <p>Kind Regards<br>
                            ##config.app.name##</p>",
        ]
    );

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('Thanks for registering with');
});

it('can preview user password reset request email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-request-reset',
            'name' => 'User Request Password Reset',
            'title' => 'Reset your password',
            'subject' => '##config.app.name## Password Reset',
            'preheader' => 'Reset Password',
            'content' => "<p>Hello ##user.name##,</p>
                            <p>You are receiving this email because we received a password reset request for your account.</p>
                            <div>{{button url='##tokenUrl##' title='Change My Password'}}</div>
                            <p>If you didn't request this password reset, no further action is needed. However if this has happened more than once in a short space of time, please let us know.</p>
                            <p>We'll never ask for your credentials over the phone or by email and you should never share your credentials</p>
                            <p>If you’re having trouble clicking the 'Change My Password' button, copy and paste the URL below into your web browser:</p>
                            <p><a href='##tokenUrl##'>##tokenUrl##</a></p>
                            <p>Kind Regards,<br>##config.app.name##</p>",
        ]
    );
    // get(EmailTemplateResource::getUrl('view', [
    //     'record' => $emailData,
    // ]))->assertSee('You are receiving this email because we received a password reset request for your account');

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('You are receiving this email because we received a password reset request for your account');
});

it('can preview user password reset success email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-password-reset-success',

            'name' => 'User Password Reset',
            'title' => 'Password Reset Success',
            'subject' => '##config.app.name## password has been reset',
            'preheader' => 'Success',
            'content' => "<p>Dear ##user.name##,</p>
                            <p>Your password has been reset.</p>
                            <p>Kind Regards,<br>##config.app.name##</p>",
        ]
    );
    // get(EmailTemplateResource::getUrl('view', [
    //     'record' => $emailData,
    // ]))->assertSee('Your password has been reset');

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('Your password has been reset');
});

it('can preview user account locked out email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-locked-out',

            'name' => 'User Account Locked Out',
            'title' => 'Account Locked',
            'subject' => '##config.app.name## account has been locked',
            'preheader' => 'Oops!',
            'content' => "<p>Dear ##user.name##,</p>
                            <p>Sorry your account has been locked out due to too many bad password attempts.</p>
                            <p>Please contact our customer services team on ##config.email-templates.customer-services.email## who will be able to help</p>
                                <p>Kind Regards,<br>##config.app.name##</p>",
        ]
    );
    // get(EmailTemplateResource::getUrl('view', [
    //     'record' => $emailData,
    // ]))->assertSee('Sorry your account has been locked out due to too many bad password attempts');

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('Sorry your account has been locked out due to too many bad password attempts');

});

it('can preview user verify email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-verify-email',
            'name' => 'User Verify Email',
            'title' => 'Verify your email',
            'subject' => 'Verify your email with ##config.app.name##',
            'preheader' => 'Gain Access Now',
            'content' => "<p>Dear ##user.name##,</p>
                            <p>Your receiving this email because your email address has been registered on ##config.app.name##.</p>
                            <p>To activate your account please click the button below.</p>
                            <div>{{button url='##verificationUrl##' title='Verify Email Address'}}</div>
                            <p>If you’re having trouble clicking the 'Verify Email Address' button, copy and paste the URL below into your web browser:</p>
                            <p><a href='##verificationUrl##'>##verificationUrl##</a></p>
                            <p>Kind Regards,<br>##config.app.name##</p>",
        ]
    );
    // get(EmailTemplateResource::getUrl('view', [
    //     'record' => $emailData,
    // ]))->assertSee('To activate your account please click the button below');

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('To activate your account please click the button below');
});

it('can preview user verified email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-verified',
            'name' => 'User Verified',
            'title' => 'Verification Success',
            'subject' => 'Verification success for ##config.app.name##',
            'preheader' => 'Verification success for ##config.app.name##',
            'content' => "<p>Hi ##user.name##,</p>
                            <p>Your email address ##user.email## has been verified on ##config.app.name##</p>
                            <p>Kind Regards,<br>##config.app.name##</p>",
        ]
    );
    // get(EmailTemplateResource::getUrl('view', [
    //     'record' => $emailData,
    // ]))->assertSee('has been verified on');

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('has been verified on');
});

it('can preview user logged in email', function () {
    $emailData = EmailTemplate::factory()->create(
        [
            'key' => 'user-login',
            'name' => 'User Logged In',
            'title' => 'Login Success',
            'subject' => 'Login Success for ##config.app.name##',
            'preheader' => 'Login Success for ##config.app.name##',
            'content' => "<p>Hi ##user.name##,</p>
                            <p>You have been logged into ##config.app.name##.</p>
                            <p>If this was not you please contact: </p>
                            <p>You can disable this email in your account notification preferences.</p>
                            <p>Kind Regards,<br>##config.app.name##</p>",
        ]
    );
    // get(EmailTemplateResource::getUrl('view', [
    //     'record' => $emailData,
    // ]))->assertSee('You have been logged into');

    $this->makeTheme();
    livewire(EditEmailTemplate::class, [
        'record' => $emailData->getRouteKey(),
    ])->mountAction(Actions\ViewAction::class, ['
        record' => $emailData,
    ])->assertSee('You have been logged into');
});
