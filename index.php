<?php
require_once 'Client.php';
$config = require 'config.php';

// --- DEINE KONFIGURATION ---
// Zugriff auf die Daten erfolgt nun über das Array:
$controller_user     = $config['controller']['user'];
$controller_password = $config['controller']['password'];
$controller_url      = $config['controller']['url'];
$site_id             = $config['controller']['site_id'];
// Das Gleiche für deine Profile

// --- 2. ZUGANGS-LISTEN & ZEITEN (in Minuten) ---
// --- ZENTRALE KONFIGURATION ---
$access_profiles = $config['profiles'];


$bg_dir = 'assets/background_images'; // Pfad zu deinen Bildern
// ---------------------------
// 1. Zufälliges Hintergrundbild auswählen
$bg_image = ''; 
if (is_dir($bg_dir)) {
    // Scannt den Ordner nach jpg, jpeg, png und webp
    // $images = preg_grep('~\.(jpeg|jpg|png|webp)$~', scandir($bg_dir));
    $images = preg_grep('/^(?!\.).+\.(jpeg|jpg|png|webp)$/i', scandir($bg_dir)); // bilder mit PUNKT am anfang unterdrücken
    if (!empty($images)) {
        $random_key = array_rand($images);
        $bg_image = $bg_dir . '/' . $images[$random_key];
    }
}


// UniFi Parameter aus der URL (kommen automatisch vom Controller)
$id = $_GET['id'] ?? null; // MAC-Adresse des Gastes
$ap = $_GET['ap'] ?? null; // MAC-Adresse des Access Points
// --- 2. FORMULAR VERARBEITUNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $email     = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $vorname   = htmlspecialchars($_POST['vorname'] ?? 'Unbekannt');
    $user_code = $_POST['access_code'] ?? ''; 
    
    // Profil-Matching (Wir nutzen das Profil-Array von vorhin)
    $chosen = $access_profiles['STANDARD']; 
    foreach ($access_profiles as $key => $data) {
        if (!empty($user_code) && in_array($user_code, $data['passwords'])) {
            $chosen = $data;
            break;
        }
    }

    // Werte aus dem gewählten Profil extrahieren
    $duration    = $chosen['duration'];
    $down        = $chosen['speed_down'];
    $up          = $chosen['speed_up'];
    $status_label = $chosen['label'];
    $welcome_msg  = sprintf($chosen['welcome'], $vorname);

    // --- 3. LOGFILE SCHREIBEN (Inklusive AP-Information) ---
    // Wir fügen [AP: ...] hinzu, damit du siehst, wo der Gast ist
    $log_line = sprintf(
        "[%s] [%-8s] Name: %-15s | AP: %s | MAC: %s | Email: %s" . PHP_EOL,
        date("Y-m-d H:i:s"),
        $status_label,
        $vorname,
        $ap ?: 'Unbekannt',
        $id,
        $email ?: 'no-email'
    );
    file_put_contents("emails.txt", $log_line, FILE_APPEND);

    // --- 4. UNIFI AUTORISIERUNG ---
    try {
        $unifi_connection = new UniFi_API\Client($controller_user, $controller_password, $controller_url, $site_id);
        $unifi_connection->login();
        
        /**
         * authorize_guest Parameter:
         * 1: MAC des Gastes ($id)
         * 2: Dauer in Minuten ($duration)
         * 3: UP-Limit Kbps ($up)
         * 4: DOWN-Limit Kbps ($down)
         * 5: MB-Limit (null)
         * 6: AP-MAC ($ap)  <-- HIER WIRD SIE EINGESETZT
         */
        $unifi_connection->authorize_guest($id, $duration, $up, $down, null, $ap);
        
        // Erfolg-Overlay
        echo "<div style='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);color:white;display:flex;justify-content:center;align-items:center;z-index:9999;text-align:center;font-family:Georgia,serif;backdrop-filter:blur(5px);'>
                <div>
                    <h1 style='font-size:2.5em;'>$welcome_msg</h1>
                    <p style='font-size:1.2em;'>Votre accès Wi-Fi (<strong>$status_label</strong>) est activé.</p>
                    <p style='opacity:0.7;'>Vitesse: " . ($down/1000) . " Mbps | Redirection en cours...</p>
                </div>
              </div>";

    } catch (Exception $e) {
        echo "<div style='position:fixed;top:0;left:0;width:100%;height:100%;background:#cc0000;color:white;display:flex;justify-content:center;align-items:center;z-index:9999;'>
                <h2>Erreur de connexion au contrôleur.</h2>
              </div>";
    }

    header("Refresh:4; url=https://www.google.com");
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résidence Wi-Fi</title>
    <style>
        body1 { font-family: sans-serif; text-align: center; padding-top: 50px; background-color: #f4f4f4; }
        .card1 { background: white; padding: 20px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        input1 { padding: 10px; width: 200px; margin-bottom: 10px; }
        button1 { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        * { box-sizing: border-box; }
        
        body, html { 
            height: 100%; 
            margin: 0; 
            padding: 0;
            overflow: hidden; /* Verhindert Scrollen bei Animationen */
        }

        /* Hintergrundbild-Konfiguration */
        .bg-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* WICHTIG: Der Slash vor dem PHP Tag */
            background: url('/<?php echo $bg_image; ?>') no-repeat center center fixed;
            background-size: cover;
            z-index: -2;
        }

        /* Platzhalter für dein späteres Animations-Canvas */
        #animation-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Über dem Bild, unter dem Formular */
            pointer-events: none; /* Klicks gehen durch das Canvas aufs Formular */
        }

        body { 
            font-family: 'Georgia', serif; /* Etwas edler für ein Château */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card { 
            background: rgba(255, 255, 255, 0.85); /* Leicht transparentes Weiß */
            padding: 40px; 
            border-radius: 15px; 
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            max-width: 400px;
            width: 90%;
            backdrop-filter: blur(5px); /* Moderner Glass-Effekt */
            z-index: 10;
        }

        h1 { color: #2c3e50; margin-bottom: 10px; }
        p { color: #34495e; margin-bottom: 30px; }
        
        input { 
            padding: 6px; 
            width: 100%; 
            margin-bottom: 10px; /* Reduziert von 20px auf 10px für engere Abstände */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        /* Spezielle Formatierung für das optionale Code-Feld */
        .input-code {
            background: rgba(255, 255, 255, 0.5);
            font-size: 0.9em;
            opacity: 0.8;
        }

        button { 
            padding: 12px 30px; 
            background: #1a252f; /* Dunkles, edles Blau/Grau */
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }

        button:hover { background: #34495e; }

        /* Container für die Checkboxen */
        .checkbox-group {
            margin-bottom: 15px; /* Abstand zum Button */
            font-size: 0.85em;
            text-align: left;
            color: #2c3e50;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px; /* Sehr geringer Abstand zwischen den Zeilen */
            line-height: 1.2;
        }

        .checkbox-item input {
            margin: 0 8px 0 0; /* Nur rechts Abstand zum Text */
            width: auto;
        }

        .success-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: white; display: flex; align-items: center; justify-content: center;
            z-index: 100; font-family: sans-serif;
        }

        .logo-top-left {
            position: fixed;
            top: 2%;
            left: 2%;
            width: 10vw; /* 10% der Fensterbreite */
            height: auto;
            z-index: 20;
            pointer-events: none;

            /* Abgerundete Ecken */
            border-radius: 15px; 

            /* Schatten nach rechts unten */
            /* Parameter: Rechts-Versatz, Unten-Versatz, Unschärfe, Farbe */
            filter: drop-shadow(8px 8px 10px rgba(0, 0, 0, 0.5));

            /* Falls dein Logo ein Hintergrund hat (kein transparentes PNG), 
               solltest du zusätzlich dies hier nutzen: */
            /* overflow: hidden; */
        }

        /* Responsive Anpassung für Mobilgeräte */
        @media (max-width: 768px) {
            .logo-top-left {
                width: 20vw;
                border-radius: 10px; /* Etwas kleinere Rundung auf kleinen Schirmen */
            }
        }

        .logo-container {
            position: fixed;
            top: 2%;
            left: 2%;
            /* 10vw bedeutet: 10% der aktuellen Breite des Browserfensters */
            width: 25vw; 
            /* Die Höhe sollte identisch sein, damit das Canvas quadratisch bleibt */
            height: 25vw; 
            z-index: 100;
            cursor: pointer;

            /* Hier passiert die Rundung */
            border-radius: 40px; 
            overflow: hidden; 
            
            /* Schatten für den edlen Look */
            filter: drop-shadow(8px 8px 10px rgba(0, 0, 0, 0.5));
        }

        #logo-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* Modal Hintergrund */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
        }

        /* Modal Box - Kompakter */
        .modal-content {
            background-color: #fff;
            margin: 5% auto; /* Weniger Abstand nach oben */
            padding: 20px;   /* Reduziert von 30px */
            border-radius: 12px;
            width: 85%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            text-align: left;
            box-shadow: 0 5px 25px rgba(0,0,0,0.4);
        }

        /* Das Scroll-Fenster innerhalb des Modals */
        .modal-text { 
            font-size: 0.85em; 
            line-height: 1.3; 
            color: #333;
            
            /* Hier passiert die Magie */
            max-height: 50vh;    /* Maximale Höhe: 50% der Bildschirmhöhe */
            overflow-y: auto;    /* Scrollbalken erscheint nur bei Bedarf */
            padding-right: 10px; /* Platz für den Scrollbalken */
            margin-bottom: 15px;
            border-top: 1px solid #eee;    /* Dezente Trennung oben */
            border-bottom: 1px solid #eee; /* Dezente Trennung unten */
            padding-top: 10px;
        }

        /* Den Scrollbalken etwas schöner machen (optional für Chrome/Safari) */
        .modal-text::-webkit-scrollbar {
            width: 6px;
        }
        .modal-text::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 10px;
        }


        /* Text-Abstände innerhalb der Modals */
        .modal-text p { 
            margin-bottom: 8px; /* Deutlich weniger Abstand zwischen Absätzen */
            line-height: 1.3;   /* Engere Zeilenführung */
            font-size: 0.9em;
        }

        .modal-text strong {
            display: block;
            margin-top: 5px;
        }

        /* Die Schließen-Buttons */
        .btn-close-modal {
            margin-top: 15px;
            padding: 10px 20px;
            /* ... restliche Styles bleiben gleich ... */
        }


        .close-modal:hover { color: black; }

    </style>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Funktion zum Öffnen
            function setupModal(linkId, modalId) {
                const link = document.getElementById(linkId);
                const modal = document.getElementById(modalId);
                if (link && modal) {
                    link.onclick = function(e) {
                        e.preventDefault();
                        modal.style.display = "block";
                    };
                }
            }

            setupModal("open_agb", "agb_modal");
            setupModal("open_rules_indoor", "rules_modal_indoor");
            setupModal("open_rules_outdoor", "rules_modal_outdoor");

            // Schließen-Logik für alle Modals
            document.querySelectorAll('.close-modal, .btn-close-modal').forEach(btn => {
                btn.onclick = function() {
                    const targetId = this.getAttribute('data-target');
                    document.getElementById(targetId).style.display = "none";
                };
            });

            // Klick außerhalb schließt alle Modals
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = "none";
                }
            };
        });
        </script>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const canvas = document.getElementById('logo-canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.src = '/assets/logo/chaudeborde_1869.png'; 

            let mouse = { x: -100, y: -100, active: false };
            const meshSize = 25; // Ein etwas feineres Gitter für weichere Verformung
            
            img.onload = function() {
                // Wir setzen die interne Auflösung etwas höher für scharfe Kanten
                canvas.width = 400; 
                canvas.height = 400;
                animate();
            };

            // Tracking für Maus und Touch
            const handleMove = (e) => {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                
                mouse.x = (clientX - rect.left) * (canvas.width / rect.width);
                mouse.y = (clientY - rect.top) * (canvas.height / rect.height);
                mouse.active = true;
            };

            window.addEventListener('mousemove', handleMove);
            window.addEventListener('touchmove', handleMove);
            window.addEventListener('mouseleave', () => { mouse.active = false; });
            window.addEventListener('touchend', () => { mouse.active = false; });

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Optional: Falls du im Canvas selbst runden willst (Sicherheitsnetz)
                const radius = 40; // Entspricht etwa den 20px im CSS (da Canvas 400px breit ist)
                ctx.beginPath();
                ctx.moveTo(radius, 0);
                ctx.lineTo(canvas.width - radius, 0);
                ctx.quadraticCurveTo(canvas.width, 0, canvas.width, radius);
                ctx.lineTo(canvas.width, canvas.height - radius);
                ctx.quadraticCurveTo(canvas.width, canvas.height, canvas.width - radius, canvas.height);
                ctx.lineTo(radius, canvas.height);
                ctx.quadraticCurveTo(0, canvas.height, 0, canvas.height - radius);
                ctx.lineTo(0, radius);
                ctx.quadraticCurveTo(0, 0, radius, 0);
                ctx.closePath();
                ctx.clip(); // Alles was jetzt gezeichnet wird, bleibt innerhalb dieser Form

                const rows = meshSize;
                const cols = meshSize;
                const cellW = canvas.width / cols;
                const cellH = canvas.height / rows;

                for (let y = 0; y < rows; y++) {
                    for (let x = 0; x < cols; x++) {
                        let drawX = x * cellW;
                        let drawY = y * cellH;

                        if (mouse.active) {
                            const dx = drawX - mouse.x;
                            const dy = drawY - mouse.y;
                            const dist = Math.sqrt(dx*dx + dy*dy);
                            if (dist < 120) { // Wirkungsbereich der Verformung
                                const force = (120 - dist) / 120;
                                // Der "Gummiband"-Effekt
                                drawX += dx * force * 0.6; 
                                drawY += dy * force * 0.6;
                            }
                        }

                        ctx.drawImage(
                            img, 
                            x * (img.width / cols), y * (img.height / rows), 
                            img.width / cols, img.height / rows,
                            drawX, drawY, 
                            cellW + 1.5, cellH + 1.5 // Überlappung verhindert weiße Linien
                        );
                    }
                }
                requestAnimationFrame(animate);
            }
        });
        </script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Setup für den Hintergrund-Canvas
    const canvas_anim = document.getElementById('animation-canvas');
    if (!canvas_anim) return;

    const ctx_anim = canvas_anim.getContext('2d');

    function resizeAnim() {
        canvas_anim.width = window.innerWidth;
        canvas_anim.height = window.innerHeight;
    }
    window.addEventListener('resize', resizeAnim);
    resizeAnim();

    // 2. Die Vogel-Klasse (Der "Château-Falke")
    class Bird {
        constructor() {
            this.reset();
        }

        reset() {
            // Startet links weit außerhalb des Sichtfelds
            this.x = -250;
            // Fliegt im oberen Bereich (Himmel)
            this.y = Math.random() * (canvas_anim.height * 0.4) + 50;
            // Zufällige Größe für Tiefenwirkung
            this.size = Math.random() * 0.4 + 0.35;
            // Geschwindigkeit (Langsamer wirkt eleganter)
            this.speed = Math.random() * 1.3 + 0.7;
            this.wingPhase = Math.random() * Math.PI * 2;
            this.active = true;
        }

        // Hilfsfunktion für die organische Flügelform
        drawBirdShape(ctx, wp, offset) {
            ctx.beginPath();
            ctx.moveTo(0, offset);
            // Linker Flügel mit sanften Kurven
            ctx.bezierCurveTo(-20, -5 + offset, -50, wp + offset, -100, (wp / 2) + offset);
            ctx.bezierCurveTo(-60, wp + 20 + offset, -20, 15 + offset, 0, offset);
            // Rechter Flügel (spiegelverkehrt)
            ctx.bezierCurveTo(20, -5 + offset, 50, wp + offset, 100, (wp / 2) + offset);
            ctx.bezierCurveTo(60, wp + 20 + offset, 20, 15 + offset, 0, offset);
            ctx.fill();
        }

        update() {
            this.x += this.speed;
            // Sanfte Wellenbewegung (Auf und Ab im Wind)
            this.y += Math.sin(this.x * 0.005) * 0.4;
            // Flügelschlag-Rhythmus
            this.wingPhase += 0.05;

            // Wenn der Vogel rechts rausfliegt, nach Pause links neu starten
            if (this.x > canvas_anim.width + 250) {
                this.active = false;
                setTimeout(() => this.reset(), Math.random() * 8000 + 4000);
            }
        }

        draw() {
            if (!this.active) return;

            ctx_anim.save();
            ctx_anim.translate(this.x, this.y);
            
            // Neigung des Körpers in der Flugkurve
            const tilt = Math.cos(this.x * 0.005) * 0.15;
            ctx_anim.rotate(tilt);
            ctx_anim.scale(this.size, this.size);

            const wingPos = Math.sin(this.wingPhase) * 35;

            // A. Schattenwurf (leicht versetzt und sehr transparent für Tiefe)
            ctx_anim.fillStyle = "rgba(0, 0, 0, 0.07)";
            this.drawBirdShape(ctx_anim, wingPos + 5, 12);

            // B. Hauptflügel (Dunkles Anthrazit)
            ctx_anim.fillStyle = "#2c3e50";
            this.drawBirdShape(ctx_anim, wingPos, 0);

            // C. Vogelkörper (Kleine Ellipse für die 3D-Optik)
            ctx_anim.fillStyle = "#34495e";
            ctx_anim.beginPath();
            ctx_anim.ellipse(0, 0, 15, 6, 0, 0, Math.PI * 2);
            ctx_anim.fill();

            ctx_anim.restore();
        }
    }

    // 3. Animation-Loop
    const birds = [new Bird(), new Bird()]; // Zwei Vögel gleichzeitig

    function animate() {
        ctx_anim.clearRect(0, 0, canvas_anim.width, canvas_anim.height);
        
        birds.forEach(bird => {
            bird.update();
            bird.draw();
        });
        
        requestAnimationFrame(animate);
    }

    animate();
});
</script>

</head>
<body>
    <div class="bg-fullscreen"></div>

    <div class="logo-container" id="logo-warp-container">
    <canvas id="logo-canvas"></canvas>
    </div>

    <canvas id="animation-canvas"></canvas>

    <div class="card">
        <h1>Résidence Wi-Fi</h1>
        <p>Bienvenue à la Résidence de Chaudeborde.<br>
        Veuillez saisir votre adresse e-mail pour activer votre accès internet.</p>
        
        <form method="POST">
            <input type="text" name="vorname" placeholder="Votre prénom (Vorname)" required>
            <input type="email" name="email" placeholder="votre@email.fr" required>
            <input type="password" name="access_code" placeholder="Code Privilège (Optionnel)" class="input-code">

            <div style="margin-bottom: 20px; font-size: 0.85em; text-align: left; color: #2c3e50;">

            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="accept_agb" name="accept_agb" required>
                    <label for="accept_agb">J'accepte les <a href="#" id="open_agb" style="font-weight:bold; text-decoration:underline; color:inherit;">conditions d'utilisation</a></label>
                </div>
                
                <div class="checkbox-item">
                    <input type="checkbox" id="accept_rules_indoor" name="accept_rules_indoor" required>
                    <label for="accept_rules_indoor">J'accepte le <a href="#" id="open_rules_indoor" style="font-weight:bold; text-decoration:underline; color:inherit;">règlement intérieur</a></label>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" id="accept_rules_outdoor" name="accept_rules_outdoor" required>
                    <label for="accept_rules_outdoor">J'accepte le <a href="#" id="open_rules_outdoor" style="font-weight:bold; text-decoration:underline; color:inherit;">règlement extérieur</a></label>
                </div>

            </div>


            <button type="submit">Se connecter</button>
        </form>

        <div id="agb_modal" class="modal">
            <div class="modal-content">
                <span class="close-modal" data-target="agb_modal">&times;</span>
                <h2>Conditions d'Utilisation</h2>
                <div class="modal-text">
                <ol style="margin-left: 0; padding-left: 1.2em;">
                    <li>
                        <strong>Service :</strong> L'accès Wi-Fi est un service gratuit offert par la Résidence. Il est notamment conçu pour assurer la réception de vos appels mobiles (Appels&nbsp;Wi-Fi) <strong>à&nbsp;l'intérieur comme à&nbsp;l'extérieur du bâtiment</strong>, palliant ainsi l'épaisseur des murs historiques et vous permettant de rester joignable dans toute la <strong>Résidence.</strong>
                    </li>
                    <li style="margin-top: 10px;">
                        <strong>Sécurité :</strong> Il est interdit de consulter des sites illégaux ou de saturer le réseau par des téléchargements abusifs afin de garantir une qualité de service optimale pour tous.
                    </li>
                    <li style="margin-top: 10px;">
                        <strong>Données :</strong> Nous collectons votre adresse e-mail pour des raisons de sécurité légale, conformément à la législation française sur la conservation des données de connexion.
                    </li>
                </ol> 
               </div>
                <button type="button" class="btn-close-modal" data-target="agb_modal">Fermer</button>
            </div>
        </div>

        <div id="rules_modal_indoor" class="modal">
            <div class="modal-content">
                <span class="close-modal" data-target="rules_modal_indoor">&times;</span>
                <h2>Règlement Intérieur</h2>
                <div class="modal-text">
                    <p><strong>Bienvenue à la Résidence de Chaudeborde.</strong></p>
                        <ol style="margin-left: 0; padding-left: 1.2em;">
                            <li><strong>Système d'évacuation :</strong> Merci de ne jeter aucun corps solide, lingette, protection hygiénique ou reste de nourriture dans les toilettes. Utilisez uniquement le papier toilette prévu à cet effet.</li>
                            <li><strong>Gestion des déchets :</strong> Un système de tri est en place. Merci de respecter les consignes de recyclage indiquées dans l'espace poubelles.</li>
                            <li><strong>Fenêtres et Sécurité :</strong> Merci de fermer les fenêtres lorsque le chauffage est allumé oder en cas d'absence pour éviter tout dommage lié aux intempéries.</li>
                            
                            <li>Merci de respecter le calme des lieux, particulièrement après 22h.</li>
                            <li>Les parties communes doivent rester propres et ordonnées.</li>
                            <li>Il est strictement interdit de fumer à l'intérieur des bâtiments.</li>
                            <li>En cas de problème technique, veuillez contacter la réception.</li>
                            <li>Veillez à la fermeture des portes d'accès pour la sécurité de la Résidence.</li>
                            <li>L'utilisation du Wi-Fi doit rester conforme à la législation et respectueuse des autres utilisateurs.</li>
                            <li>Le mobilier doit être respecté ; merci de signaler tout dommage éventuel.</li>
                        </ol>
                </div>
                <button type="button" class="btn-close-modal" data-target="rules_modal_indoor">Fermer</button>
            </div>
        </div>


        <div id="rules_modal_outdoor" class="modal">
            <div class="modal-content">
                <span class="close-modal" data-target="rules_modal_outdoor">&times;</span>
                <h2>Règlement Extérieur – Le Domaine</h2>
                <div class="modal-text">
                <p><strong>Côté sérieux :</strong></p>
                    <ol style="margin-left: 0; padding-left: 1.2em; margin-bottom: 15px;">
                        <li><strong>Vidéosurveillance :</strong> Pour votre sécurité et la protection de ce site historique, les espaces extérieurs et les accès sont placés sous vidéosurveillance.</li>
                        <li>Le jardin situé à l'arrière de la maison est une oasis de calme, réservée exclusivement aux hôtes de la Résidence.</li>
                        <li>Nous aimons notre nature : merci de ne laisser aucun déchet et d'éviter les rassemblements trop bruyants.</li>
                        <li>Le mobilier et la décoration se sentent mieux à la Résidence : merci de ne pas les emporter comme "souvenirs".</li>
                        <li>Les feux de camp et barbecues sont interdits (nous préférons garder la Résidence intacte).</li>
                    </ol>

                <p><strong>Côté détente (avec le sourire) :</strong></p>
                    <ol start="5" style="margin-left: 0; padding-left: 1.2em;">
                        <li>Nourrir les fantômes de la maison après minuit est à vos risques et périls. Ils sont gentils, mais ont tendance à chanter l'opéra la nuit.</li>
                        <li>Discuter du sens de la vie avec les statues est autorisé, mais ne vous attendez pas à une réponse. Elles sont un peu têtues.</li>
                        <li>Chevaucher les lions en pierre ou tout autre élément décoratif est déconseillé, tant pour votre élégance que pour la santé de leurs colonnes vertébrales.</li>
                        <li>Le rire est non seulement autorisé mais vivement encouragé – sauf s'il s'agit d'un rire de "méchant de film" qui effraie les oiseaux du parc.</li>
                    </ol>
                <button type="button" class="btn-close-modal" data-target="rules_modal_outdoor">J'ai compris, c'est parti !</button>
            </div>
        </div>


    </div>


</body>
</html>



