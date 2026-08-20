<?php

namespace App\Console\Commands;

use App\Mail\PruebaCorreo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProbarCorreo extends Command
{
    /**
     * @var string
     */
    protected $signature = 'blog:probar-correo {correo? : Destinatario de la prueba}';

    /**
     * @var string
     */
    protected $description = 'Envia un correo de prueba para verificar la configuracion de MAIL_*';

    public function handle(): int
    {
        $correo = (string) ($this->argument('correo') ?? config('blog.admin_email'));

        if ($correo === '') {
            $this->components->error('Define BLOG_ADMIN_EMAIL o pasa el correo como argumento.');

            return self::FAILURE;
        }

        $this->components->info("Enviando con el driver '".config('mail.default')."' a {$correo}...");

        try {
            Mail::to($correo)->send(new PruebaCorreo);
        } catch (Throwable $error) {
            $this->components->error('Fallo el envio: '.$error->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Correo enviado. Si el driver es "log", revisa storage/logs/laravel.log.');

        return self::SUCCESS;
    }
}
