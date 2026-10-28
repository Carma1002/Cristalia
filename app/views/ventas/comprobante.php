<?php $logo = logo_cliente(); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Comprobante <?= e($v['numero']) ?></title>
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link href="assets/vendor/fonts/fonts.css" rel="stylesheet">
  <style>
    :root { --azul: #0F536B; --cian: #3FA7BF; --hielo: #D7E9EF; --bruma: #F4F7F8; --pizarra: #5E6E77; --grafito: #1C2830; }
    * { box-sizing: border-box; }
    body { font-family: 'Inter', Arial, sans-serif; font-size: 13px; color: var(--grafito); background: var(--bruma); margin: 0; padding: 24px 16px; }
    .hoja { max-width: 780px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 36px 40px; box-shadow: 0 10px 30px -15px rgba(9,46,61,.3); border-bottom: 6px solid var(--azul); }
    .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
    .emp { display: flex; gap: 14px; align-items: center; }
    .emp img { width: 58px; height: 58px; border-radius: 50%; }
    h1 { font-family: 'Poppins', sans-serif; font-weight: 500; font-size: 18px; margin: 0; color: var(--grafito); }
    .muted { color: var(--pizarra); }
    .doc { text-align: right; }
    .doc .eti { font-size: 10px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #1F7F99; }
    .doc .num { font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 500; color: var(--azul); }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; margin: 26px 0 18px; padding: 16px 18px; background: var(--bruma); border-radius: 12px; }
    .grid b { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: var(--pizarra); font-weight: 600; margin-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: var(--pizarra); padding: 10px 8px; border-bottom: 1.5px solid var(--azul); }
    td { padding: 10px 8px; border-bottom: 1px solid #EDF2F4; }
    .r { text-align: right; }
    .tot { margin-left: auto; width: 290px; margin-top: 14px; }
    .tot div { display: flex; justify-content: space-between; padding: 5px 8px; }
    .tot .g { font-family: 'Poppins', sans-serif; font-size: 18px; color: var(--azul); border-top: 1px dashed #cdd9de; margin-top: 4px; padding-top: 10px; }
    .anulada { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 999px; background: #FBE5E2; color: #B3392F; font-weight: 600; font-size: 11px; }
    .aviso { margin-top: 26px; padding: 10px; text-align: center; font-size: 11px; color: var(--pizarra); border: 1px dashed #cdd9de; border-radius: 10px; }
    .pie { margin-top: 18px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: var(--pizarra); }
    .pie img { height: 14px; vertical-align: middle; }
    .acciones { max-width: 780px; margin: 0 auto 14px; display: flex; gap: 8px; }
    .acciones button { font: inherit; padding: 9px 16px; border-radius: 10px; border: 1px solid #d5e1e6; background: #fff; cursor: pointer; }
    .acciones button.pri { background: var(--azul); color: #fff; border-color: var(--azul); }
    @media print { body { background: #fff; padding: 0; } .hoja { box-shadow: none; border-radius: 0; padding: 10px 6px; } .acciones { display: none; } }
  </style>
</head>
<body>
  <div class="acciones"><button class="pri" onclick="window.print()">🖨 Imprimir</button><button onclick="window.close()">Cerrar</button></div>
  <div class="hoja">
    <div class="top">
      <div class="emp">
        <?php if ($logo): ?><img src="<?= e($logo) ?>" alt=""><?php endif; ?>
        <div><h1><?= e(ajuste('empresa_nombre')) ?></h1>
          <div class="muted">RIF <?= e(ajuste('empresa_rif')) ?><?= ajuste('empresa_direccion') ? ' · ' . e(ajuste('empresa_direccion')) : '' ?></div>
          <div class="muted">Sucursal <?= e($v['sucursal']) ?><?= $v['sucursal_tel'] ? ' · ' . e($v['sucursal_tel']) : '' ?></div></div>
      </div>
      <div class="doc"><div class="eti">Comprobante de venta</div><div class="num"><?= e($v['numero']) ?></div><div class="muted"><?= fecha($v['fecha']) ?></div>
        <?php if ($v['estado'] === 'ANULADA'): ?><span class="anulada">ANULADA</span><?php endif; ?></div>
    </div>
    <div class="grid">
      <div><b>Cliente</b><?= e($v['cliente']) ?></div><div><b>C.I. / RIF</b><?= e($v['cliente_doc']) ?></div>
      <div><b>Teléfono</b><?= e($v['cliente_tel'] ?: '—') ?></div><div><b>Método de pago</b><?= e($v['metodo_pago']) ?></div>
      <div><b>Pedido</b><?= e($v['pedido_numero']) ?></div><div><b>Atendido por</b><?= e($v['usuario'] ?: '—') ?></div>
    </div>
    <table>
      <thead><tr><th>Código</th><th>Descripción</th><th class="r">Cant.</th><th class="r">P. unit.</th><th class="r">Subtotal</th></tr></thead>
      <tbody><?php foreach ($det as $d): ?><tr><td class="muted"><?= e($d['codigo']) ?></td><td><?= e($d['nombre']) ?></td><td class="r"><?= (int)$d['cantidad'] ?></td>
        <td class="r"><?= money($d['precio_unitario']) ?></td><td class="r"><?= money($d['subtotal']) ?></td></tr><?php endforeach; ?></tbody>
    </table>
    <div class="tot">
      <div><span class="muted">Subtotal</span><span><?= money($v['subtotal']) ?></span></div>
      <div><span class="muted">Descuento</span><span>− <?= money($v['descuento']) ?></span></div>
      <div class="g"><span>Total</span><span><?= money($v['total']) ?></span></div>
    </div>
    <div class="aviso">DOCUMENTO ADMINISTRATIVO INTERNO · NO VÁLIDO COMO FACTURA FISCAL</div>
    <div class="pie"><span>Gracias por su compra.</span><span>Generado con <img src="assets/img/cristalia-horizontal.svg" alt="Cristalia"></span></div>
  </div>
</body>
</html>
