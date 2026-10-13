<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateTehsilOfficersTest extends TestCase
{
    use RefreshDatabase;

    private string $csv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->csv = sys_get_temp_dir().'/tehsil-officers-'.uniqid().'.csv';
    }

    protected function tearDown(): void
    {
        @unlink($this->csv);
        parent::tearDown();
    }

    public function test_creates_one_field_officer_per_tehsil_with_villages(): void
    {
        $this->artisan('users:tehsil-officers', ['--credentials' => $this->csv])->assertSuccessful();

        $tehsilsWithVillages = Tehsil::whereIn('id', Site::whereIn('status', ['ongoing', 'tendering'])->select('tehsil_id'))->get();
        $this->assertGreaterThan(0, $tehsilsWithVillages->count());

        foreach ($tehsilsWithVillages as $tehsil) {
            $officer = User::where('name', "Field Officer {$tehsil->name}")->where('email', 'like', 'field.%@prmsc.punjab.gov.pk')->firstOrFail();
            $this->assertTrue($officer->hasRole('Field Officer'));
            $this->assertSame([$tehsil->id], $officer->tehsils()->pluck('tehsils.id')->all());
        }

        $this->assertNull(User::where('email', 'field.ahmedpursial@prmsc.punjab.gov.pk')->first());
        $this->assertFileExists($this->csv);
    }

    public function test_tehsil_officer_logs_in_on_website_and_mobile_app_and_sees_only_their_tehsil(): void
    {
        $this->artisan('users:tehsil-officers', ['--credentials' => $this->csv, '--password' => 'Password123!'])->assertSuccessful();

        $taunsa = Tehsil::where('name', 'Taunsa')->firstOrFail();
        $taunsaVillage = Site::where('tehsil_id', $taunsa->id)->where('status', 'ongoing')->firstOrFail();
        $otherVillage = Site::where('tehsil_id', '!=', $taunsa->id)->where('status', 'ongoing')->firstOrFail();

        // Website login
        $this->post('/login', ['email' => 'field.taunsa@prmsc.punjab.gov.pk', 'password' => 'Password123!'])
            ->assertRedirect(route('field.today'));
        $this->get('/today')->assertOk()
            ->assertSee($taunsaVillage->village_name)
            ->assertDontSee($otherVillage->village_name);
        auth()->logout();

        // Mobile app login
        $token = $this->postJson('/api/mobile/login', [
            'email' => 'field.taunsa@prmsc.punjab.gov.pk',
            'password' => 'Password123!',
            'device_name' => 'Test phone',
        ])->assertOk()->json('token');

        $sites = collect($this->withToken($token)->getJson('/api/mobile/bootstrap')->assertOk()->json('sites'));
        $this->assertNotEmpty($sites);
        $this->assertTrue($sites->every(fn ($site) => $site['tehsil_id'] === $taunsa->id));
    }

    public function test_passwords_from_an_existing_credentials_file_are_reused(): void
    {
        file_put_contents($this->csv, "tehsil,email,password\nTaunsa,field.taunsa@prmsc.punjab.gov.pk,KnownPass1\n");

        $this->artisan('users:tehsil-officers', ['--credentials' => $this->csv])->assertSuccessful();

        $officer = User::where('email', 'field.taunsa@prmsc.punjab.gov.pk')->firstOrFail();
        $this->assertTrue(Hash::check('KnownPass1', $officer->password));
    }

    public function test_running_again_keeps_existing_passwords_and_does_not_duplicate(): void
    {
        $this->artisan('users:tehsil-officers', ['--credentials' => $this->csv])->assertSuccessful();
        $hash = User::where('email', 'field.taunsa@prmsc.punjab.gov.pk')->value('password');
        $count = User::count();

        unlink($this->csv);
        $this->artisan('users:tehsil-officers', ['--credentials' => $this->csv])->assertSuccessful();

        $this->assertSame($count, User::count());
        $this->assertSame($hash, User::where('email', 'field.taunsa@prmsc.punjab.gov.pk')->value('password'));
    }
}
