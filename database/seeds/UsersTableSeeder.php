<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\User;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder {
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run() {
    DB::table('users')->insert([
      'username' => 'w',
      'password' => Hash::make('secret'),
      'created_at' => Carbon::now(),
      'updated_at' => Carbon::now(),
    ]);

    factory(User::class, 50)->create();
  }
}
