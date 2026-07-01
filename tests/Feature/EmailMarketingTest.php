<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\RecipientType;
use App\Enums\UserRole;
use App\Jobs\SendEmailCampaignJob;
use App\Livewire\Admin\EmailMarketing\CampaignForm;
use App\Livewire\Admin\EmailMarketing\CampaignIndex;
use App\Livewire\Admin\EmailMarketing\CampaignReport;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class EmailMarketingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $user->assignRole('administrador');
        return $user;
    }

    private function makeEditor(): User
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);
        $user->assignRole('editor');
        return $user;
    }

    private function makeCampaign(array $attrs = []): EmailCampaign
    {
        return EmailCampaign::create(array_merge([
            'name'           => 'Campanha Teste',
            'subject'        => 'Assunto teste',
            'from_name'      => 'Feira',
            'from_email'     => 'noreply@feira.com',
            'body_html'      => '<p>Olá, {{nome}}!</p>',
            'recipient_type' => RecipientType::AllSubscribers,
            'status'         => CampaignStatus::Draft,
            'created_by'     => $this->makeAdmin()->id,
        ], $attrs));
    }

    // ─── Acesso ──────────────────────────────────────────────────────────────

    public function test_admin_can_access_email_marketing_index(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)
            ->test(CampaignIndex::class)
            ->assertOk();
    }

    public function test_editor_cannot_access_email_marketing(): void
    {
        $editor = $this->makeEditor();

        $this->actingAs($editor)
            ->get(route('admin.email-marketing.index'))
            ->assertForbidden();
    }

    // ─── CampaignIndex ────────────────────────────────────────────────────────

    public function test_admin_can_duplicate_draft_campaign(): void
    {
        $admin    = $this->makeAdmin();
        $campaign = $this->makeCampaign(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(CampaignIndex::class)
            ->call('duplicate', $campaign->id);

        $this->assertDatabaseCount('email_campaigns', 2);
        $this->assertDatabaseHas('email_campaigns', ['name' => 'Cópia de Campanha Teste', 'status' => 'draft']);
    }

    public function test_admin_can_delete_draft_campaign(): void
    {
        $admin    = $this->makeAdmin();
        $campaign = $this->makeCampaign(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(CampaignIndex::class)
            ->call('delete', $campaign->id);

        $this->assertDatabaseMissing('email_campaigns', ['id' => $campaign->id]);
    }

    public function test_cannot_delete_sent_campaign(): void
    {
        $admin    = $this->makeAdmin();
        $campaign = $this->makeCampaign([
            'created_by' => $admin->id,
            'status'     => CampaignStatus::Sent,
        ]);

        Livewire::actingAs($admin)
            ->test(CampaignIndex::class)
            ->call('delete', $campaign->id);

        // Campanha enviada não pode ser deletada — deve permanecer no banco
        $this->assertDatabaseHas('email_campaigns', ['id' => $campaign->id]);
    }

    public function test_send_now_enqueues_job_and_creates_recipient_sends(): void
    {
        Queue::fake();
        $admin = $this->makeAdmin();

        NewsletterSubscriber::create(['name' => 'Ana', 'email' => 'ana@test.com', 'is_active' => true]);
        NewsletterSubscriber::create(['name' => 'Bob', 'email' => 'bob@test.com', 'is_active' => true]);
        NewsletterSubscriber::create(['name' => 'Inativo', 'email' => 'x@test.com', 'is_active' => false]);

        $campaign = $this->makeCampaign(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(CampaignIndex::class)
            ->call('sendNow', $campaign->id);

        Queue::assertPushedOn('email-marketing', SendEmailCampaignJob::class);
        $this->assertDatabaseCount('email_campaign_sends', 2);
    }

    // ─── CampaignForm ────────────────────────────────────────────────────────

    public function test_admin_can_create_draft_campaign_via_form(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)
            ->test(CampaignForm::class)
            ->set('name', 'Minha Campanha')
            ->set('subject', 'Novidades de julho')
            ->set('bodyHtml', '<p>Olá!</p>')
            ->call('save');

        $this->assertDatabaseHas('email_campaigns', [
            'name'    => 'Minha Campanha',
            'status'  => 'draft',
        ]);
    }

    public function test_campaign_form_validates_required_fields(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)
            ->test(CampaignForm::class)
            ->set('name', '')
            ->set('subject', '')
            ->set('bodyHtml', '')
            ->call('save')
            ->assertHasErrors(['name', 'subject', 'bodyHtml']);
    }

    public function test_send_test_email_delivers_to_logged_user(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)
            ->test(CampaignForm::class)
            ->set('name', 'Teste')
            ->set('subject', 'Assunto')
            ->set('fromName', 'Feira')
            ->set('fromEmail', 'noreply@feira.com')
            ->set('bodyHtml', '<p>Conteúdo</p>')
            ->call('sendTestEmail');

        Mail::assertSent(CampaignMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_estimated_recipients_updates_on_type_change(): void
    {
        $admin = $this->makeAdmin();

        NewsletterSubscriber::create(['name' => 'Sub 1', 'email' => 'sub1@test.com', 'is_active' => true]);
        NewsletterSubscriber::create(['name' => 'Sub 2', 'email' => 'sub2@test.com', 'is_active' => true]);

        $component = Livewire::actingAs($admin)
            ->test(CampaignForm::class)
            ->set('recipientType', 'all_subscribers');

        $this->assertEquals(2, $component->get('estimatedRecipients'));
    }

    // ─── Pixel e descadastro ─────────────────────────────────────────────────

    public function test_tracking_pixel_marks_email_as_opened(): void
    {
        $campaign = $this->makeCampaign();
        $send     = EmailCampaignSend::create([
            'campaign_id' => $campaign->id,
            'email'       => 'reader@test.com',
        ]);

        $this->assertNull($send->opened_at);

        $this->get(route('mk.open', $send->tracking_pixel_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        $this->assertNotNull($send->fresh()->opened_at);
    }

    public function test_unsubscribe_page_shows_confirmation_form_with_valid_token(): void
    {
        $campaign = $this->makeCampaign();
        $email    = 'unsubscriber@test.com';
        $secret   = config('app.marketing_unsubscribe_secret', config('app.key'));
        $token    = hash_hmac('sha256', $email . '|' . $campaign->id, $secret);

        $this->get(route('newsletter.unsubscribe', ['token' => $token, 'email' => $email, 'campaign' => $campaign->id]))
            ->assertOk()
            ->assertSee($email);
    }

    public function test_unsubscribe_confirm_deactivates_newsletter_subscriber(): void
    {
        $campaign = $this->makeCampaign();
        $email    = 'leave@test.com';

        NewsletterSubscriber::create(['name' => 'Leave User', 'email' => $email, 'is_active' => true]);
        EmailCampaignSend::create(['campaign_id' => $campaign->id, 'email' => $email]);

        $secret = config('app.marketing_unsubscribe_secret', config('app.key'));
        $token  = hash_hmac('sha256', $email . '|' . $campaign->id, $secret);

        $this->post(route('newsletter.unsubscribe.confirm'), [
            'token'    => $token,
            'email'    => $email,
            'campaign' => $campaign->id,
        ])->assertRedirect();

        $this->assertFalse(NewsletterSubscriber::where('email', $email)->first()->is_active);
        $this->assertNotNull(EmailCampaignSend::where('email', $email)->first()->unsubscribed_at);
    }

    // ─── SendEmailCampaignJob ────────────────────────────────────────────────

    public function test_send_job_delivers_emails_and_marks_sends(): void
    {
        Mail::fake();
        $admin    = $this->makeAdmin();
        $campaign = $this->makeCampaign([
            'created_by' => $admin->id,
            'status'     => CampaignStatus::Scheduled,
        ]);

        EmailCampaignSend::create(['campaign_id' => $campaign->id, 'email' => 'r1@test.com']);
        EmailCampaignSend::create(['campaign_id' => $campaign->id, 'email' => 'r2@test.com']);

        (new SendEmailCampaignJob($campaign->id))->handle();

        Mail::assertSent(CampaignMail::class, 2);

        $fresh = $campaign->fresh();
        $this->assertEquals(CampaignStatus::Sent, $fresh->status);
        $this->assertEquals(2, $fresh->sent_count);
        $this->assertNotNull($fresh->sent_at);
    }

    // ─── CampaignReport ──────────────────────────────────────────────────────

    public function test_admin_can_view_campaign_report(): void
    {
        $admin    = $this->makeAdmin();
        $campaign = $this->makeCampaign(['created_by' => $admin->id, 'status' => CampaignStatus::Sent, 'sent_count' => 1]);

        Livewire::actingAs($admin)
            ->test(CampaignReport::class, ['id' => $campaign->id])
            ->assertOk()
            ->assertSee($campaign->name);
    }
}
