<?php
final class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) redirect('dashboard');
        if (is_post()) {
            $err = Auth::attempt((string)input('usuario', ''), (string)($_POST['password'] ?? ''));
            if ($err === null) redirect('dashboard');
            flash('danger', $err);
            keep_old(['usuario' => input('usuario')]);
            redirect('login');
        }
        View::render('auth/login', ['title' => 'Iniciar sesión'], 'layout_auth');
    }

    public function logout(): void
    {
        $this->requirePost();
        Auth::logout();
        session_start();
        flash('success', 'Sesión cerrada correctamente.');
        redirect('login');
    }

    public function perfil(): void
    {
        $u = Auth::user();
        if (is_post()) {
            $actual = (string)($_POST['actual'] ?? '');
            $nueva = (string)($_POST['nueva'] ?? '');
            $conf = (string)($_POST['confirmar'] ?? '');
            $hash = DB::value('SELECT password_hash FROM usuarios WHERE id = ?', [$u['id']]);
            if (!password_verify($actual, (string)$hash)) throw new AppException('La contraseña actual no es correcta.');
            UsuarioController::validarPassword($nueva, $conf);
            DB::update('usuarios', ['password_hash' => password_hash($nueva, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
            Bitacora::log('seguridad', 'cambio_clave', 'El usuario cambió su contraseña');
            flash('success', 'Contraseña actualizada.');
            redirect('perfil');
        }
        $this->view('auth/perfil', ['title' => 'Mi perfil', 'u' => $u]);
    }
}
