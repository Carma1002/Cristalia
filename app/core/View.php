<?php
/**
 * Capa de presentación: renderiza plantillas PHP dentro del layout.
 */
final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        $content = self::partial($view, $data);
        if ($layout === '') { echo $content; return; }
        echo self::partial($layout, array_merge($data, ['content' => $content]));
        clear_old();
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = APP_PATH . '/views/' . $view . '.php';
        if (!is_file($file)) throw new RuntimeException("Vista no encontrada: $view");
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }
}
