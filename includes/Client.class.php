<?php

class ClientException extends Exception {

}

class Client
{
    private $id_client;
    private $db;
    private $prenom, $nom, $email, $password, $adresse, $codepostal, $ville, $telephone, $credit, $infoCompte;

    public function __construct()
    {
        $this->db = DBmanager::getInstance();
    }

    public function setIdClient($id_client)
    {
        $this->id_client = $id_client;
    }

    public function fetchInfos()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        $prep_fetchInfos = $this->db->prepare("SELECT * FROM CLIENTS WHERE ID_CLIENT = ?");
        $prep_fetchInfos->execute(array(
            $this->id_client
        ));

        $infos = $prep_fetchInfos->fetch();

        if(!empty($infos)) 
        {
            $this->prenom = $infos['PRENOM'];
            $this->nom = $infos['NOM'];
            $this->email = $infos['EMAIL'];
            $this->password = $infos['PASSWORD'];
            $this->adresse = $infos['ADRESSE'];
            $this->codepostal = $infos['CODEPOSTAL'];
            $this->ville = $infos['VILLE'];
            $this->telephone = $infos['TELEPHONE'];
            $this->credit = $infos['CREDIT'];
            $this->infoCompte = $infos['TYPE_COMPTE'];

            return true;
        }
        else
        {
            return false;
        }
    }

    public function ajouterCredit($somme)
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        $somme = floatval($somme);

        if($somme <= 0)
        {
            throw new ClientException("La somme à ajouter doit être supérieure ou égale à 0");
        }

        $addCredit = $this->db->prepare("UPDATE CLIENTS SET CREDIT = CREDIT + :ajout WHERE ID_CLIENT = :id_client");
        $addCredit->bindParam(':ajout', $somme, PDO::PARAM_INT);
        $addCredit->bindParam(':id_client', $this->id_client, PDO::PARAM_INT);
        
        return $addCredit->execute();
    }

    public function deduireCredit($somme)
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        $somme = floatval($somme);

        if($somme <= 0)
        {
            throw new ClientException("La somme à déduire doit être supérieure ou égale à 0");
        }



        // On vérifie que le crédit est suffisant
        if(($this->getCredit() - $somme) >= 0)
        {
            $deduireCredit = $this->db->prepare("UPDATE CLIENTS SET CREDIT = CREDIT - :supp WHERE ID_CLIENT = :id_client");
            $deduireCredit->bindParam(':supp', $somme, PDO::PARAM_INT);
            $deduireCredit->bindParam(':id_client', $this->id_client, PDO::PARAM_INT);
    
            return $deduireCredit->execute();
        }
        else
        {
            return false;
        }
    }

    public function getCredit($update = false)
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        if($update)
        {
            if(!$this->fetchInfos())
            {
                throw new ClientException("Impossible de mettre à jour les données");
            }
        }

        return $this->credit;
    }

    public function getPrenom()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }
        
        return $this->prenom;
    }

    public function getEmail()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }
        
        return $this->email;
    }


    public function getNom()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->nom;
    }

    public function getPassword()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->password;
    }
     
     public function getAdresse()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->adresse;
    }
    public function getCode()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->codepostal;
    }
    public function getVille()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->ville;
    }
    public function getTel()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->telephone;
    }

    public function getInfoCompte()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->infoCompte;
    }
        public function getId()
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        return $this->id_client;
    }

    public static function listerClients()
    {
        $prep_liste = DBmanager::getInstance()->prepare("SELECT * FROM CLIENTS");
        $prep_liste->execute();

        return $prep_liste->fetchAll(PDO::FETCH_ASSOC);
    }

    public function supprimerClient($confirm = false)
    {
        if(empty($this->id_client))
        {
            throw new ClientException("La classe n'est pas initialisée avec un Id");
        }

        if(!$confirm)
        {
            throw new ClientException("Le flag de confirmation n'est pas activé");
        }

        // Suppression des souscriptions
        $souscriptionObj = new Souscription;
        $souscriptionObj->setIdClient($this->id_client);

        foreach($souscriptionObj->listerSouscriptions() as $souscription)
        {
            $souscriptionObj->resilierSouscription($souscription['IDENTIFIANT_SOUSCRIPTION']);
        }

        // Suppression des tickets
        $ticketObj = new Ticket;
        $ticketObj->supprimerTickets($this->id_client);

        // Suppression des factures
        $factureObj = new Facture;
        $factureObj->supprimerFactures($this->id_client);

        // Finalement on supprime le client de la base
        $prep_supprimerClient = $this->db->prepare("DELETE FROM CLIENTS WHERE ID_CLIENT = ?");
        
        if($prep_supprimerClient->execute(array($this->id_client)) === false)
        {
            throw new ClientException("Une erreur SQL s'est produite lors de la suppression de l'utilisateur");
        }
    }

}