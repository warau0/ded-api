<?php

namespace App\Rules;
use Illuminate\Contracts\Validation\Rule;
use GuzzleHttp\Client;

class ValidRecaptcha implements Rule {
  public function passes($attribute, $value) {
    $client = new Client([
        'base_uri' => 'https://google.com/recaptcha/api/'
    ]);

    $response = $client->post('siteverify', [
      'query' => [
        'secret' => env('GOOGLE_RECAPTCHA_SECRET'),
        'response' => $value
      ]
    ]);

    return json_decode($response->getBody())->success;
  }

  public function message() {
    return 'ReCaptcha verification failed.';
  }
}
