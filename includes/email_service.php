<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private PHPMailer $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    private function setupSMTP(): void {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_SECURE;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            
            // Default sender
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        } catch (Exception $e) {
            error_log("Email setup error: " . $e->getMessage());
        }
    }
    
    public function sendAppointmentConfirmation(array $appointment, array $client, array $agent): bool {
        try {
            // Reset mailer
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Email to client
            $this->mailer->addAddress($client['email'], $client['prenom'] . ' ' . $client['nom']);
            $this->mailer->Subject = 'Confirmation de votre rendez-vous - RDV Impôts Matsiatra';
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getClientEmailTemplate($appointment, $client, $agent);
            $this->mailer->AltBody = $this->getClientEmailText($appointment, $client, $agent);
            
            $this->mailer->send();
            
            // Reset for admin email
            $this->mailer->clearAddresses();
            $this->mailer->addAddress(ADMIN_EMAIL, ADMIN_NAME);
            $this->mailer->Subject = 'Nouveau rendez-vous - ' . $client['prenom'] . ' ' . $client['nom'];
            $this->mailer->Body = $this->getAdminEmailTemplate($appointment, $client, $agent);
            $this->mailer->AltBody = $this->getAdminEmailText($appointment, $client, $agent);
            
            $this->mailer->send();
            
            return true;
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getClientEmailTemplate(array $appointment, array $client, array $agent): string {
        $date = date('d/m/Y', strtotime($appointment['date_rdv']));
        $heure = substr($appointment['heure_rdv'], 0, 5);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Confirmation de rendez-vous</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;'>
                    <h1 style='color: #2c3e50; margin-bottom: 10px;'>RDV Impôts Matsiatra</h1>
                    <h2 style='color: #27ae60; margin-bottom: 20px;'>Confirmation de votre rendez-vous</h2>
                </div>
                
                <div style='background-color: white; padding: 20px; border-radius: 8px; margin-top: 20px;'>
                    <p>Bonjour {$client['prenom']} {$client['nom']},</p>
                    
                    <p>Votre rendez-vous a été confirmé avec succès.</p>
                    
                    <div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='color: #27ae60; margin-top: 0;'>Détails du rendez-vous</h3>
                        <p><strong>Date :</strong> {$date}</p>
                        <p><strong>Heure :</strong> {$heure}</p>
                        <p><strong>Motif :</strong> {$appointment['motif']}</p>
                        <p><strong>Agent :</strong> {$agent['prenom']} {$agent['nom']}</p>
                    </div>
                    
                    <p><strong>Notes :</strong></p>
                    <p>" . ($appointment['notes_client'] ?: 'Aucune note') . "</p>
                    
                    <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h4 style='color: #856404; margin-top: 0;'>Important</h4>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>Veuillez arriver 10 minutes avant l'heure du rendez-vous</li>
                            <li>Apportez tous les documents nécessaires</li>
                            <li>En cas d'annulation, contactez-nous au moins 24h à l'avance</li>
                        </ul>
                    </div>
                    
                    <p>Cordialement,<br>
                    <strong>L'équipe RDV Impôts Matsiatra</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getClientEmailText(array $appointment, array $client, array $agent): string {
        $date = date('d/m/Y', strtotime($appointment['date_rdv']));
        $heure = substr($appointment['heure_rdv'], 0, 5);
        
        return "
        RDV Impôts Matsiatra - Confirmation de rendez-vous
        
        Bonjour {$client['prenom']} {$client['nom']},
        
        Votre rendez-vous a été confirmé avec succès.
        
        Détails du rendez-vous :
        - Date : {$date}
        - Heure : {$heure}
        - Motif : {$appointment['motif']}
        - Agent : {$agent['prenom']} {$agent['nom']}
        
        Notes : " . ($appointment['notes_client'] ?: 'Aucune note') . "
        
        Important :
        - Veuillez arriver 10 minutes avant l'heure du rendez-vous
        - Apportez tous les documents nécessaires
        - En cas d'annulation, contactez-nous au moins 24h à l'avance
        
        Cordialement,
        L'équipe RDV Impôts Matsiatra";
    }
    
    private function getAdminEmailTemplate(array $appointment, array $client, array $agent): string {
        $date = date('d/m/Y', strtotime($appointment['date_rdv']));
        $heure = substr($appointment['heure_rdv'], 0, 5);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouveau rendez-vous</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;'>
                    <h1 style='color: #1976d2; margin-bottom: 10px;'>RDV Impôts Matsiatra</h1>
                    <h2 style='color: #1976d2; margin-bottom: 20px;'>Nouveau rendez-vous</h2>
                </div>
                
                <div style='background-color: white; padding: 20px; border-radius: 8px; margin-top: 20px;'>
                    <p>Un nouveau rendez-vous a été créé.</p>
                    
                    <div style='background-color: #f3e5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='color: #7b1fa2; margin-top: 0;'>Détails du rendez-vous</h3>
                        <p><strong>Client :</strong> {$client['prenom']} {$client['nom']} ({$client['email']})</p>
                        <p><strong>Date :</strong> {$date}</p>
                        <p><strong>Heure :</strong> {$heure}</p>
                        <p><strong>Motif :</strong> {$appointment['motif']}</p>
                        <p><strong>Agent :</strong> {$agent['prenom']} {$agent['nom']}</p>
                        <p><strong>Statut :</strong> {$appointment['status']}</p>
                    </div>
                    
                    <p><strong>Notes du client :</strong></p>
                    <p>" . ($appointment['notes_client'] ?: 'Aucune note') . "</p>
                    
                    <p>Ce rendez-vous a été automatiquement confirmé.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getAdminEmailText(array $appointment, array $client, array $agent): string {
        $date = date('d/m/Y', strtotime($appointment['date_rdv']));
        $heure = substr($appointment['heure_rdv'], 0, 5);
        
        return "
        RDV Impôts Matsiatra - Nouveau rendez-vous
        
        Un nouveau rendez-vous a été créé.
        
        Détails du rendez-vous :
        - Client : {$client['prenom']} {$client['nom']} ({$client['email']})
        - Date : {$date}
        - Heure : {$heure}
        - Motif : {$appointment['motif']}
        - Agent : {$agent['prenom']} {$agent['nom']}
        - Statut : {$appointment['status']}
        
        Notes du client : " . ($appointment['notes_client'] ?: 'Aucune note') . "
        
        Ce rendez-vous a été automatiquement confirmé.";
    }
}
