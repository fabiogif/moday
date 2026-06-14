<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class HashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avato:test {string} {--requests=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $string = $this->argument('string');
        $request = $this->option('requests');

        $hashService = new Hash;

        for($i = 1; $i <= $request; $i++)
        {
            $response = Http::get('http://localhost/hash', ['string' => $string]);

            if($response->getStatusCode() === 429)
            {
                $this->info('Sleeping in'.$i);
                sleep(60);
            }
        }

        return Command::SUCCESS;

    }
}
