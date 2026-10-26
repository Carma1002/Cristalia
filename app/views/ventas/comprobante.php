<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Comprobante <?= e($v['numero']) ?></title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #222; max-width: 760px; margin: 20px auto; padding: 0 16px; }
    h1 { font-size: 18px; margin: 0; color: #0f4c81; } h2 { font-size: 15px; margin: 12px 0 4px; }
    .muted { color: #666; } table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px; border-bottom: 1px solid #ddd; text-align: left; } th { background: #0f4c81; color: #fff; }
    .r { text-align: right; } .tot td { border: 0; } .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; margin-top: 10px; }
    .aviso { border: 1px dashed #999; padding: 8px; margin-top: 16px; font-size: 11px; text-align: center; }
    .anulada { color: #b00; font-weight: bold; font-size: 16px; border: 2px solid #b00; display: inline-block; padding: 2px 8px; }
    .acciones { margin-bottom: 14px; } @media print { .acciones { display: none; } body { margin: 0; } }
  </style>
</head>
<body>
  <div class="acciones"><button onclick="window.print()">🖨 Imprimir</button> <button onclick="window.close()">Cerrar</button></div>
  <h1><?= e(ajuste('empresa_nombre')) ?></h1>
  <div class="muted">RIF <?= e(ajuste('empresa_rif')) ?> · <?= e(ajuste('empresa_direccion')) ?></div>
  <div class="muted">Sucursal <?= e($v['sucursal']) ?><?= $v['sucursal_dir'] ? ' · ' . e($v['sucursal_dir']) : '' ?><?= $v['sucursal_tel'] ? ' · ' . e($v['sucursal_tel']) : '' ?></div>
  <h2>COMPROBANTE DE VENTA N° <?= e($v['numero']) ?></h2>
  <?php if ($v['estado'] === 'ANULADA'): ?><div class="anulada">ANULADA</div><?php endif; ?>
  <div class="grid">
    <div><strong>Fecha:</strong> <?= fecha($v['fecha']) ?></div><div><strong>Pedido:</strong> <?= e($v['pedido_numero']) ?></div>
    <div><strong>Cliente:</strong> <?= e($v['cliente']) ?></div><div><strong>C.I./RIF:</strong> <?= e($v['cliente_doc']) ?></div>
    <div><strong>Teléfono:</strong> <?= e($v['cliente_tel'] ?: '—') ?></div><div><strong>Método de pago:</strong> <?= e($v['metodo_pago']) ?></div>
  </div>
  <table>
    <thead><tr><th>Código</th><th>Descripción</th><th class="r">Cant.</th><th class="r">P. unit.</th><th class="r">Subtotal</th></tr></thead>
    <tbody><?php foreach ($det as $d): ?><tr><td><?= e($d['codigo']) ?></td><td><?= e($d['nombre']) ?></td><td class="r"><?= (int)$d['cantidad'] ?></td>
      <td class="r"><?= money($d['precio_unitario']) ?></td><td class="r"><?= money($d['subtotal']) ?></td></tr><?php endforeach; ?></tbody>
    <tfoot class="tot">
      <tr><td colspan="4" class="r">Subtotal</td><td class="r"><?= money($v['subtotal']) ?></td></tr>
      <tr><td colspan="4" class="r">Descuento</td><td class="r">− <?= money($v['descuento']) ?></td></tr>
      <tr><td colspan="4" class="r"><strong>TOTAL</strong></td><td class="r"><strong><?= money($v['total']) ?></strong></td></tr>
    </tfoot>
  </table>
  <p class="muted">Atendido por: <?= e($v['usuario'] ?: '—') ?></p>
  <div class="aviso">DOCUMENTO ADMINISTRATIVO INTERNO · NO VÁLIDO COMO FACTURA FISCAL</div>
</body>
</html>
