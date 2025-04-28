<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de contacto</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #121A26;
            font-family: 'Poppins', sans-serif;
            color: #F5F5F5;
        }

        .email-wrapper {
            max-width: 700px;
            margin: 40px auto;
            background-color: #243344;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
            border: 2px solid rgb(228, 168, 83);
        }

        .email-header {
            background-color: rgb(228, 168, 83);
            color: #243344;
            padding: 30px;
            text-align: center;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .email-body {
            padding: 40px;
            background-color: #243344;
            font-size: 16px;
            line-height: 1.8;
        }

        .email-body h2 {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            color: rgb(228, 168, 83);
        }

        .email-body p {
            margin-bottom: 20px;
        }

        blockquote {
            margin: 20px 0;
            padding: 20px;
            background-color: #2F4058;
            border-left: 5px solid rgb(228, 168, 83);
            font-style: italic;
            color: #EEE;
        }

        .email-footer {
            padding: 25px 40px;
            text-align: center;
            background-color: #1C2533;
            font-size: 13px;
            color: #BBB;
        }

        .email-footer a {
            color: rgb(228, 168, 83);
            text-decoration: none;
            font-weight: 500;
        }

        .branding {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: rgb(228, 168, 83);
        }

        .emoji {
            font-size: 1.3em;
            margin-left: 6px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>Tu mensaje ha sido recibido con éxito</h1>
        </div>

        <div class="email-body">
            <h2>Hola {{ $data['name'] }} <span class="emoji">🐾</span></h2>

            <p>Gracias por dar el primer paso hacia una vida más feliz para tu mejor amigo.</p>

            <p>En <strong>SmartPx</strong> no solo caminamos perros…</p>
            <blockquote>Nos aseguramos de que cada paseo sea una experiencia saludable, divertida y diseñada especialmente para tu peludo.</blockquote>

            <p>Hemos recibido tu mensaje y ya estamos moviendo colas de emoción por ayudarte. Un miembro de nuestro equipo se pondrá en contacto contigo muy pronto.</p>

            <p>✨ Tu confianza significa mucho. Estás más cerca de darle a tu perro lo que se merece: <strong>una vida activa, plena y guiada por tecnología de bienestar.</strong></p>

            <p>Nos emociona acompañarte en este viaje hacia una experiencia canina superior.</p>
        </div>

        <div class="email-footer">
            <p class="branding">SmartPx — Paseos inteligentes para tu perro</p>
            <p>
                <a href="https://smartpx.org">smartpx.org</a> |
                smartpetsxplore@gmail.com
            </p>
        </div>
    </div>
</body>
</html>
