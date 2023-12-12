<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra</title>
    <!-- Agrega aquí tus estilos CSS personalizados si es necesario -->
</head>
<body>
    <!-- Agrega aquí el contenido de la orden de compra utilizando tablas y formato HTML -->
    <h1>Orden de Compra</h1>
    
    <!-- Información de la entidad compradora y proveedor -->
    <table width="100%">
        <tr>
            <td width="50%"><strong>Entidad Compradora:</strong><br>{{ $datos['entidad']['nombre'] }}<br>{{ $datos['entidad']['direccion'] }}<br>{{ $datos['entidad']['ruc'] }}</td>
            <td width="50%"><strong>Proveedor:</strong><br>{{ $datos['proveedor']['nombre'] }}<br>{{ $datos['proveedor']['direccion'] }}<br>{{ $datos['proveedor']['ruc'] }}</td>
        </tr>
    </table>

    <!-- Detalles de la orden de compra -->
    <table width="100%" border="1">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['detalles'] as $detalle)
                <tr>
                    <td>{{ $detalle['producto'] }}</td>
                    <td>{{ $detalle['cantidad'] }}</td>
                    <td>{{ $detalle['precio_unitario'] }}</td>
                    <td>{{ $detalle['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Total y condiciones de entrega -->
    <p><strong>Total:</strong> {{ $datos['total'] }}</p>
    <p><strong>Condiciones de Entrega:</strong><br>{{ $datos['condiciones_entrega'] }}</p>

    <!-- Observaciones -->
    <p><strong>Observaciones:</strong><br>{{ $datos['observaciones'] }}</p>

    <!-- Firma y sello autorizados -->
    <p><strong>Firma y Sello Autorizados:</strong></p>

    <!-- Continúa con más contenido si es necesario -->

</body>
</html>
