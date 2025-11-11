<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur Orange Money</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            color: #ff6600;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .welcome-message {
            font-size: 18px;
            color: #ff6600;
            margin-bottom: 20px;
        }
        .account-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ff6600;
        }
        .account-info h3 {
            margin-top: 0;
            color: #ff6600;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .security-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Orange Money</div>
            <h1 class="welcome-message">Bienvenue {{ $compte->nom }} !</h1>
            <p>Votre compte Orange Money a été créé avec succès.</p>
        </div>

        <div class="account-info">
            <h3>Informations de votre compte</h3>
            <div class="info-item">
                <span class="info-label">ID Client :</span> {{ $compte->id_client }}
            </div>
            <div class="info-item">
                <span class="info-label">Numéro de compte :</span> {{ $compte->numero_compte }}
            </div>
            <div class="info-item">
                <span class="info-label">Type de compte :</span> {{ ucfirst($compte->type_compte) }}
            </div>
            <div class="info-item">
                <span class="info-label">Statut :</span> {{ ucfirst($compte->statut_compte) }}
            </div>
            <div class="info-item">
                <span class="info-label">Téléphone :</span> {{ $compte->telephone }}
            </div>
            <div class="info-item">
                <span class="info-label">Date de création :</span> {{ $compte->created_at->format('d/m/Y H:i') }}
            </div>
        </div>

        <div class="security-note">
            <strong>Note de sécurité :</strong><br>
            Conservez ces informations en lieu sûr. Ne partagez jamais vos identifiants de connexion avec qui que ce soit.
        </div>

        <p>
            Vous pouvez maintenant utiliser votre compte Orange Money pour effectuer des transactions sécurisées.
            Téléchargez l'application Orange Money sur votre smartphone pour une expérience optimale.
        </p>

        <p>
            Pour toute question ou assistance, contactez notre service client au 1212.
        </p>

        <div class="footer">
            <p>
                Cet email a été envoyé automatiquement par Orange Money.<br>
                © {{ date('Y') }} Orange Money - Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>