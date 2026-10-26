<?php
/**
 * Controlador base.
 */
abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function requirePost(): void
    {
        if (!is_post()) {
            http_response_code(405);
            throw new AppException('Operación no permitida.');
        }
    }

    protected function json(mixed $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Lanza error si el registro no existe. */
    protected function found(?array $row, string $what = 'El registro'): array
    {
        if (!$row) throw new AppException("$what no existe.");
        return $row;
    }

    /** Sucursal seleccionada en filtros respetando la restricción del usuario. */
    protected function sucursalFiltro(): ?int
    {
        return Auth::sucursalFija() ?? input_int('sucursal_id');
    }
}
