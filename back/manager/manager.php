<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/back/entity/user.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/back/entity/medecin.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/back/entity/dossier-ad.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/back/manager/identifiant.php');
session_start();
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// require LES Fonction de php mailer
// Load Composer's autoloader


//equire '../../vendor/autoload.php';
//require '../../vendor/autoload.php';

require $_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/vendor/phpmailer/phpmailer/src/Exception.php';
require $_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'].'/Projet_hopital/vendor/PHPMailer/PHPMailer/src/SMTP.php';

class manager
{

//Connexion à la base de données
  public function connexionBdd()
  {
    try {
      $db = new PDO('mysql:host='.$_ENV["bdd_host"].';dbname='.$_ENV["bdd_name"].';charset=utf8', $_ENV["bdd_user"], $_ENV["bdd_password"]);
    } catch (Exception $e) {
      die('Error:' . $e->getMessage());
    }
    return $db;
  }

  public function signIn($u)
  {
    $sql = $this->connexionBdd()->prepare('SELECT * FROM utilisateur WHERE mail = :mail');
    $sql->execute(array(
        'mail' => $u->getMail()
    ));
    $result = $sql->fetch();
    if ($result['statut'] == "admin") {
      $_SESSION['mail'] = $u->getMail();
      $_SESSION['statut'] = $result['statut'];
      $_SESSION['id'] = $result['id']; // Si la colonne id = 1, on le redirige vers le panel_admin
      header('Location: ../view/panel_admin.php ');
    } elseif (password_verify($u->getMdp(), $result['mdp'])) { // On décrypte le mot de passe, et on vérifie qu'il correspond au POST['pwd']
      $_SESSION['statut'] = $result['statut'];
      $_SESSION['mail'] = $u->getMail();
      $_SESSION['id'] = $result['id'];
      echo '<body onLoad="alert(\'Bienvenue sur votre compte\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/index.php">';
    } else {
      echo '<body onLoad="alert(\'Mot de passe ou mail incorrect !\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/connexion.php">';
    }
  }

  public function insertUser(User $u)
  {
    $sql = $this->connexionBdd()->prepare('SELECT mail FROM utilisateur WHERE mail = :mail');
    $sql->execute(array('mail' => $u->getMail()));
    $res = $sql->fetch();
    if ($res) {
      echo '<body onLoad="alert(\'Adresse mail déjà utilisée\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/inscription.php">';
    } else {
      $sql = $this->connexionBdd()->prepare("INSERT INTO utilisateur (nom, prenom, sexe, mail, mdp, statut)
      VALUES(:nom, :prenom, :sexe, :mail, :mdp, :statut)");
      $sql->execute(array(
          'nom' => $u->getNom(),
          'prenom' => $u->getPrenom(),
          'sexe' => $u->getSexe(),
          'mail' => $u->getMail(),
          'mdp' => $u->getMdp(),
          'statut' => $u->getStatut()
      ));
      $this->mail($u);
      echo '<body onLoad="alert(\'Compte créé avec succès\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/connexion.php">';
    }
  }

  public function modifierProfil(User $u)
  {
    $sql = $this->connexionBdd()->prepare('UPDATE utilisateur SET nom=:nom, prenom=:prenom, mail=:mail, sexe=:sexe WHERE id=:id');
    $res = $sql->execute(array(
        'nom' => $u->getNom(),
        'prenom' => $u->getPrenom(),
        'mail' => $u->getMail(),
        'sexe' => $u->getSexe(),
        'id' => $u->getId()
    ));
    if ($res) {
      echo '<body onLoad="alert(\'Informations enregistrées\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/moncompte.php">';
    } else {
      echo '<body onLoad="alert(\'Enregistrements non valides ! Veuillez réessayer ultérieurement !\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/moncompte.php">';
    }
  }

  public function modifyPwd(User $u)
  {
    $sql = $this->connexionBdd()->prepare('UPDATE utilisateur SET mdp=:mdp WHERE id=:id');
    $sql->execute(array(
        'mdp' => $u->getMdp()
    ));
  }

  public function afficherInfoProfil($mail)
  {
    $sql = $this->connexionBdd()->prepare('SELECT * FROM utilisateur WHERE mail=:mail');
    $sql->execute(array(
        'mail' => $mail
    ));
    $result = $sql->fetch();
    return $result;
  }

  public function saisirMail(User $mail)
  {
    $sql = $this->connexionBdd()->prepare('SELECT COUNT(*) as nb FROM user WHERE mail=:mail');
    $sql->execute(array('mail' => $mail->getMail()));
    $sql->fetch();
  }

  public function nouveauMdp(User $u)
  {
    $option = ['cost' => 15];
    $hashedPwd = password_hash($u->getMdp(), PASSWORD_DEFAULT, $option);
    $sql = $this->connexionBdd()->prepare('UPDATE user SET mdp=:mdp WHERE mail=:mail');
    $result = $sql->execute(array(
        'mail' => $u->getMail(),
        'mdp' => $hashedPwd
    ));
  }

  public function displayHours()
  {
    $sql = $this->connexionBdd()->prepare('SELECT * FROM heure');
    $sql->execute();
    $result = $sql->fetchAll();
    return $result;
  }

  public function lemedecin()
  {
    $sql = $this->connexionBdd()->prepare('SELECT * FROM medecin');
    $sql->execute();
    $result = $sql->fetchAll();
    return $result;

  }

  public function displayUser()
  {
    $sql = $this->connexionBdd()->prepare('SELECT * FROM utilisateur');
    $sql->execute();
    $result = $sql->fetchAll();
    return $result;
  }

  public function ajoutDossierAdmission(User $u, Dossier $dossier)
  {
    $db = $this->connexionBdd();
    $sql = $db->prepare("INSERT INTO dossier (id_patient, date_naissance, adresse_post, mutuelle, num_ss, optn, regime)
    VALUES (:id_patient, :date_naissance, :adresse_post, :mutuelle, :num_ss, :optn, :regime)");
    $res = $sql->execute(array(
        'date_naissance' => $dossier->getDate_naissance(),
        'adresse_post' => $dossier->getAdresse_post(),
        'mutuelle' => $dossier->getMutuelle(),
        'optn' => $dossier->getOptn(),
        'regime' => $dossier->getRegime()
    ));
    $id = $db->lastInsertId();


    if ($res) {
      echo '<body onLoad="alert(\'Informations du dossier enregistrées\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/moncompte.php">';
    } else {
      echo '<body onLoad="alert(\'Veuillez remplir les champs du formulaire\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/dossierAdmission.php">';
    }
  }

  public function adminAddUsers(User $u) {
    $db = $this->connexionBdd();
    $sql = $db->prepare('SELECT * FROM utilisateur WHERE mail=:mail');
    $sql->execute(array(
      'mail'=>$u->getMail()
    ));
    $result = $sql->fetch();
    if($result == TRUE) {
      echo '<body onLoad="alert(\'Adresse mail ou autres informations déjà utilisées\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/adminAjoutUsers.php">';
    }
    else {
      $sql = $db->prepare('INSERT INTO utilisateur(nom, prenom, sexe, mail, mdp, statut)
      VALUES(:nom, :prenom, :sexe, :mail, :mdp, :statut)');
      $sql->execute(array(
        'nom'=>$u->getNom(),
        'prenom'=>$u->getPrenom(),
        'sexe'=>$u->getSexe(),
        'mail'=>$u->getMail(),
        'mdp'=>$u->getMdp(),
        'statut'=>$u->getStatut()
      ));
      echo '<body onLoad="alert(\'Compte HSP créé avec succès\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/view/panel_admin.php">';
    }
  }

  public function adminAddMedecins(User $u, Medecin $m) {
    $db = $this->connexionBdd();
    $sql = $db->prepare('SELECT * FROM utilisateur WHERE mail=:mail');
    $result = $sql->execute(array(
      'mail'=>$u->getMail()
    ));
    if($result) {
      echo '<body onLoad="alert(\'Adresse mail ou autres informations déjà utilisées\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/adminAjoutPraticiens.php">';
    }
    else {
      $sql = $db->prepare('INSERT INTO utilisateur(nom, prenom, sexe, mail, statut)
      VALUES(:nom, :prenom, :sexe, :mail, :statut)');
      $sql->execute(array(
        'nom'=>$u->getNom(),
        'prenom'=>$u->getPrenom(),
        'sexe'=>$u->getSexe(),
        'mail'=>$u->getMail(),
        'statut'=>getStatut()
      ));
      $sql = $db->prepare('SELECT id FROM utilisateur WHERE nom=:nom AND prenom=:prenom');
      $sql->execute(array(
        'nom'=>$u->getNom(),
        'prenom'=>$u->getPrenom()
      ));
      $result = $sql->fetch();
      $m->setId_user($result['id']);
      $sql = $db->prepare('INSERT INTO medecin(id_user, id_specialite, telephone, ville) VALUES(:id_user, :id_specialite, :telephone, :ville)');
      $sql->execute(array(
        'id_user'=>$m->getId_user(),
        'id_specialite'=>$m->getIdSpecialite(),
        'telephone'=>$m->getTelephone(),
        'ville'=>$m->getVille()
      ));
      echo '<body onLoad="alert(\'Informations enregistrées avec succès\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/view/panel_admin.php">';
    }
  }

  public function afficherUtilisateurs() {
  $sql = $this->connexionBdd()->prepare('SELECT nom, prenom, sexe, mail, statut FROM utilisateur');
  $sql->execute();
  $result = $sql->fetchAll();
  return $result;
}

public function afficherSpecialites() {
  $sql = $this->connexionBdd()->prepare('SELECT * FROM specialites');
  $sql->execute();
  $result = $sql->fetchAll();
  return $result;
}

  public function priseRDV($data)
  {
//    var_dump($data);
    $sql = $this->connexionBdd()->prepare('SELECT id FROM utilisateur WHERE nom=:nom');
    $sql->execute([
        'nom' => $data['utilisateur']
    ]);
    $resultpatient = $sql->fetch();

//    var_dump($resultpatient);

    $sql = $this->connexionBdd()->prepare('SELECT id FROM utilisateur WHERE statut="medecin" AND mail=:mail');
    $sql->execute([
        'mail' => $_SESSION['mail']
    ]);
    $resultmedecin = $sql->fetch();
//    var_dump($resultmedecin);
//    var_dump($_SESSION);
    $sql = $this->connexionBdd()->prepare('SELECT id FROM heure WHERE heure=:heure');
    $sql->execute([
        'heure' => $data['heure']
    ]);
    $resultheure = $sql->fetch();
//    var_dump($resultheure);
    $sql = $this->connexionBdd()->prepare('INSERT INTO rdv (id_utilisateur, id_heure, id_medecin)
      VALUES (:id_utilisateur, :id_heure, :id_medecin)');
      $res = $sql->execute([
        'id_medecin' => $resultmedecin['id'],
        'id_utilisateur' => $resultpatient['id'],
        'id_heure' => $resultheure['id']
    ]);
//    echo "heure : " . $resultheure['id'];
//    echo "patient : " . $resultpatient['id'];
//    echo "medecin : " . $resultmedecin['id'];
//    var_dump($sql);
//    var_dump($res);
//    echo $res;
//    exit;

    if ($res) {
      echo '<body onLoad="alert(\'Prise de rendez-vous réussie\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/rdvmedecins.php">';
    } else {
      echo '<body onLoad="alert(\'Erreur dans la prise de RDV\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/rdvmedecins.php">';
    }
  }

  public function getNombySession()
  {
    $sql = $this->connexionBdd()->prepare('SELECT id from utilisateur where mail=:mail');
    $sql->execute(array(
        'mail' => $_SESSION['mail']));
    $res = $sql->fetch();
    return $res;


  }


  public function priseRDVpatient($infordv)
  {
    var_dump($_POST);
    $sql = $this->connexionBdd()->prepare('SELECT id FROM utilisateur WHERE id=:id');
    $sql->execute([
        'id' => $_SESSION['id']
    ]);
    $resultpatient = $sql->fetch();



    $sql = $this->connexionBdd()->prepare('SELECT id FROM medecin WHERE nom_medecin=:nom');
    $sql->execute([
        'nom' => $_POST['medecin']
    ]);
    $resultmedecin = $sql->fetch();
    $sql = $this->connexionBdd()->prepare('SELECT id FROM heure WHERE heure=:heure');
    $sql->execute([
        'heure' => $_POST['rdvpatiant']
    ]);
    $resultheure = $sql->fetch();
    $sql = $this->connexionBdd()->prepare('INSERT INTO rdv (id_utilisateur, id_heure, id_medecin)
      VALUES (:id_utilisateur, :id_heure, :id_medecin)');
    $res = $sql->execute([
        'id_medecin' => $resultmedecin['id'],
        'id_utilisateur' => $resultpatient['id'],
        'id_heure' => $resultheure['id']
    ]);

    if ($res) {
      echo '<body onLoad="alert(\'Prise de rendez-vous réussie\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/rdv.php">';
    } else {
      echo '<body onLoad="alert(\'Erreur dans la prise de RDV\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/rdv.php">';
    }
  }
  public function getLesrdv()
  {
    $sql = $this->connexionBdd()->prepare('SELECT utilisateur.nom,utilisateur.prenom, medecin.nom_medecin, heure.heure FROM utilisateur,medecin,heure, rdv WHERE rdv.id_medecin=medecin.id and rdv.id_heure=heure.id and utilisateur.id=rdv.id_utilisateur AND rdv.id_utilisateur=:id');
    $sql->execute(array(
        'id' => $_SESSION['id']
    ));
    $res = $sql->fetchAll();
    return $res;

  }

  public function getRdvHeure($data){
    $sql = $this->connexionBdd()->prepare('SELECT date_rdv FROM heure WHERE date_rdv =:date_rdv AND heure = :heure');
    $sql->execute(array(
        'date_rdv' => $data['daterdv'],
      'heure' => $data['heure']
    ));
    $res = $sql->fetch();
    return $res;
  }

  public function insertDate($data){

    $sql = $this->connexionBdd()->prepare('UPDATE heure SET date_rdv=:date_rdv WHERE heure=:heure');
    $sql->execute(array(
        'heure' => $data['heure'],
        'date_rdv' => $data['daterdv']
    ));

  }

  public function getUserRdv(){

    if($_SESSION['statut'] == "patient"){
    $sql = $this->connexionBdd()->prepare('SELECT utilisateur.nom, heure.heure, heure.date_rdv, rdv.id
FROM rdv
INNER JOIN utilisateur ON rdv.id_utilisateur=utilisateur.id
INNER JOIN heure ON rdv.id_heure=heure.id
WHERE rdv.id_utilisateur = :id_utilisateur');
    $sql->execute(array(
        'id_utilisateur' => $_SESSION['id'],
    ));
      $res = $sql->fetchAll();
      return $res;
    }
    if($_SESSION['statut'] == "medecin"){
      $sql = $this->connexionBdd()->prepare('SELECT utilisateur.nom, heure.heure, heure.date_rdv, rdv.id
FROM rdv
INNER JOIN utilisateur ON rdv.id_utilisateur=utilisateur.id
INNER JOIN heure ON rdv.id_heure=heure.id
WHERE rdv.id_medecin = :id_medecin');
      $sql->execute(array(
          'id_medecin' => $_SESSION['id'],
      ));
      $res = $sql->fetchAll();
      return $res;
    }
  }

  public function annulerRDV($data){
    $sql = $this->connexionBdd()->prepare('DELETE FROM `rdv` WHERE id=:id');
    $sql->execute(array(
        'id' => $data['id']
    ));
      echo '<body onLoad="alert(\'Annulation réussie\')">';
      echo '<meta http-equiv="refresh" content="0;URL=/Projet_hopital/forms/rdvmedecins.php">';
  }
  public function mail($a){
    //PHP MAILER
    //Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);
var_dump($mail);

    try {
      //Server settings
      $mail->CharSet = 'UTF-8';
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
      $mail->isSMTP();                                            // Send using SMTP
      $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
      $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
      $mail->Username   = 'tom441325@gmail.com';                     // SMTP username
      $mail->Password   = 'Loldelol2';                               // SMTP password
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS encouraged
      $mail->Port       = 465;                                    // TCP port to connect to, use 465 for PHPMailer::ENCRYPTION_SMTPS above

      //Recipients
      $mail->setFrom('tom441325@gmail.com', 'NE PAS REPONDRE');
      $mail->addAddress( $a->getMail() , $a->getNom());     // Add a recipient
      $mail->addReplyTo('tom441325@gmail.com', 'NE PAS REPONDRE');
      // Attachments
      //  $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
      //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

      // Content
      //$mail->isHTML(true);                                  // Set email format to HTML
      $mail->Subject = 'Bienvenue ! ';
      $mail->Body    = 'Bienvenue sur le site du Lycée de <b>Dugny!</b> : https://www.google.fr';
      $mail->AltBody = 'Bienvenue sur le site du Lycée de Dugny!';

      $mail->send();
      echo 'Message has been sent';
    } catch (Exception $e) {
      echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
  }
}
?>
