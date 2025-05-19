-- Création de la base de données
CREATE DATABASE IF NOT EXISTS invader;
USE invader;

#------------------------------------------------------------
# Table: Categories
#------------------------------------------------------------

CREATE TABLE Categories(
    id_categorie    INT AUTO_INCREMENT NOT NULL,
    nom_categorie   VARCHAR(250) NOT NULL,
    nbr_produit_cat INT NOT NULL,
    CONSTRAINT Categories_PK PRIMARY KEY (id_categorie)
) ENGINE=InnoDB;

#------------------------------------------------------------
# Table: Fournisseurs
#------------------------------------------------------------

CREATE TABLE Fournisseurs(
    Idf           INT AUTO_INCREMENT NOT NULL,
    fournisseur   VARCHAR(250) NOT NULL,
    nbr_produit_f INT NOT NULL,
    CONSTRAINT Fournisseurs_PK PRIMARY KEY (Idf)
) ENGINE=InnoDB;

#------------------------------------------------------------
# Table: Produits
#------------------------------------------------------------

CREATE TABLE Produits (
    id_produit INT AUTO_INCREMENT PRIMARY KEY,
    Nom VARCHAR(255) NOT NULL,
    id_categorie INT NOT NULL,
    Idf INT NOT NULL,
    conditionnement VARCHAR(255) NOT NULL,
    seuil_critique INT NOT NULL,
    stock_max INT NOT NULL,
    etat VARCHAR(32) NOT NULL,
    stock_actuel INT NOT NULL,
    FOREIGN KEY (id_categorie) REFERENCES Categories(id_categorie),
    FOREIGN KEY (Idf) REFERENCES Fournisseurs(Idf)
) ENGINE=InnoDB;

#------------------------------------------------------------
# Table: utilisateur
#------------------------------------------------------------

CREATE TABLE utilisateur(
    idu    INT AUTO_INCREMENT NOT NULL,
    Nom    VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    mail   VARCHAR(250) NOT NULL,
    mdp    VARCHAR(250) NOT NULL,
    Acces  INT NOT NULL,
    CONSTRAINT utilisateur_PK PRIMARY KEY (idu)
) ENGINE=InnoDB;

#------------------------------------------------------------
# Table: Inventaire
#------------------------------------------------------------

CREATE TABLE Inventaire(
    idi  INT AUTO_INCREMENT NOT NULL,
    date DATE NOT NULL,
    idu  INT NOT NULL,
    CONSTRAINT Inventaire_PK PRIMARY KEY (idi),
    CONSTRAINT Inventaire_utilisateur_FK FOREIGN KEY (idu) REFERENCES utilisateur(idu) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

#------------------------------------------------------------
# Table: inventaire_items
#------------------------------------------------------------

CREATE TABLE inventaire_items(
    idi        INT NOT NULL,
    id_produit INT NOT NULL,
    qte_avant  INT NOT NULL,
    qte_apres  INT NOT NULL,
    variation  INT NOT NULL,
    CONSTRAINT inventaire_items_PK PRIMARY KEY (idi, id_produit),
    CONSTRAINT inventaire_items_Inventaire_FK FOREIGN KEY (idi) REFERENCES Inventaire(idi) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT inventaire_items_Produits_FK FOREIGN KEY (id_produit) REFERENCES Produits(id_produit) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

#------------------------------------------------------------
# Données de test
#------------------------------------------------------------

-- Insertion des catégories
INSERT INTO Categories (nom_categorie, nbr_produit_cat)
VALUES 
('Boissons', 2),
('Alcools forts', 1);

-- Insertion des fournisseurs
INSERT INTO Fournisseurs (fournisseur, nbr_produit_f)
VALUES 
('Coca-Cola Company', 1),
('Orangina Suntory', 1);

-- Insertion des produits (ajout de stock_actuel)
INSERT INTO Produits (Nom, id_categorie, Idf, conditionnement, seuil_critique, stock_max, etat, stock_actuel)
VALUES 
('Coca-Cola', 1, 1, 'Bouteille 1L', 10, 50, 'satisfaisant', 25),
('Orangina', 1, 2, 'Canette 33cl', 5, 30, 'critique', 1);

-- Insertion des utilisateurs
INSERT INTO utilisateur (Nom, prenom, mail, mdp, Acces)
VALUES 
('Admin', 'User', 'admin@example.com', 'password123', 1),
('John', 'Doe', 'john.doe@example.com', 'password456', 2);

-- Insertion des inventaires
INSERT INTO Inventaire (date, idu)
VALUES 
('2025-05-13', 1);

-- Insertion des items d'inventaire
INSERT INTO inventaire_items (idi, id_produit, qte_avant, qte_apres, variation)
VALUES 
(1, 1, 20, 25, 5),
(1, 2, 10, 5, -5);