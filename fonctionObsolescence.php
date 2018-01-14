<?php

	/**
          * Cette fonction va permettre de récupérer les différentes dates servant à déterminer l'obsolescence d'un logiciel .
		  * @param $loginame: Le nom de la table.
		  * @param $dbv: Le nom de la dataBaseVersion.
		  * @param $se: Le nom du Systeme d'exploitation.
		  * @param $fv: La date de sortie du logiciel.
	  
	*/
	function selectObscolescence($loginame,$dbv,$se,$fv)
	{
		
			$tableName = $loginame;
            $bd=new PDO("mysql:host=localhost;dbname=mises_a_jours","root","");
            $bd->query('SET NAMES utf8');
            $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $requete=$bd->prepare("SELECT distinct Scope_Supported_Until, Database_Supported_Until, Operating_System_Supported_Until FROM $tableName where Database_version= :dataBaseVersion and Operating_System = :SystemE and Valid_from= :FormValid");
			$requete->bindValue(':dataBaseVersion',$dbv);
			$requete->bindValue(':SystemE',$se);
			$requete->bindValue(':FormValid',$fv);
			$requete->execute();
            $tab=$requete->fetchAll(PDO::FETCH_ASSOC);
		
			return $tab;
	}


	/**
          * Cette fonction va determiner si un logiciel est obsolète .
		  * @param $loginame: Le nom de la table.
		  * @param $dbv: Le nom de la dataBaseVersion.
		  * @param $se: Le nom du Systeme d'exploitation.
		  * @param $fv: La date de sortie du logiciel.
	  
	*/
	function estObsolete( $loginame,$dbv,$se,$fv )
	{
			$tab = selectObscolescence($loginame,$dbv,$se,$fv);
			
			$dateScope;#variable permettant de stocker la date de Scope_Supported_Until
			$dateDB;#variable permettant de stocker la date de Database_Supported_Until
			$dateOS;#variable permettant de stocker la date de Operating_System_Supported_Until
			foreach($tab as $cle => $val)
			{
                foreach($val as $cle1 =>$val1)
				{
					
					if ($cle1 == "Database_Supported_Until" )
					{
						$dateDB = $val1;
					}
					if ( $cle1 == "Scope_Supported_Until")
					{
						$dateScope = $val1;
					}
					if ( $cle1 == "Operating_System_Supported_Until" ) 
					{
						$dateOS = $val1;
					}
				}
			}
			
			
			$today = new DateTime(); //La date d'aujourd hui.
			
			$finDateScope = new DateTime($dateScope); // je caste en date
			$finDateDB = new DateTime($dateDB); 
			$finDateOS = new DateTime($dateOS); 
			
			
			$interval1 = (integer)$today->diff($finDateScope)->format('%R%a ');//Je fait la différence entre 2 date pour savoir laquelle est passé avant l'autre.
			$interval2 = (integer)$today->diff($finDateDB)->format('%R%a ');
			$interval3 = (integer)$today->diff($finDateOS)->format('%R%a ');
			$plusPetit = $finDateScope->format('y.m.d');//permet de stocker la petite date entre les 3.
			
			if ( $interval1 > $interval2 )
			{
				
				$plusPetit = $finDateDB->format('y.m.d');
			}
			if ( $interval2 > $interval3 )
			{
				$plusPetit = $finDateOS->format('y.m.d');
			}
			if( $interval1> 0 and $interval2 > 0 and $interval3 > 0)
			{
						
				echo"<p style=color:#82C46C;>Votre logiciel n'est pas encore obsolète car nous ne sommes pas le : $plusPetit</br></p>";
					
			}
			else if($interval1 < 0 or $interval2 < 0 or $interval3 < 0)
			{
				
				echo"<p style=color:#850606;> Le logiciel est obsolète depuis le $plusPetit. </p></br>";
				$today = explode('-',$today->format('y-m-d'));
				$today= implode("-",$today);
				ChercheVersionLogiciel($loginame,$today,$se);
			}
			else
			{
				echo "<p style=color:#FF4500;>Le logiciel sera obslète demain. il est temps de mettre à jour votre logiciel.</p>"	;
			}
			
			
            
		
		
	}
	
	/**
          * Cette fonction va lister les logiciels qu'on va pouvoir télécharger à la palce de l'ancien.
		  * @param $nom: Le nom de la table.
		  * @param $today: La date d'aujourd hui.
		  * @param $se: Le nom du Systeme d'exploitation.
		 	  
	*/
	
	function ChercheVersionLogiciel($nom,$today,$se )
	{
		 $table = substr($nom,0,-4);// on enlève les 4 dernier caractère du nom de la table.
		 
		 $numerosVersion = substr($nom,-1);// on prélève le dernier caractère qui est le numéros de la version du logiciel 
		
		 $bd=new PDO("mysql:host=localhost;dbname=mises_a_jours","root","");
		 $bd->query('SET NAMES utf8');
         $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		 $requete=$bd->prepare("SHOW TABLES like '$table%'");	//Retourne le nom de toutes mes tables commencant par $table
		 $requete->execute();
         $tab=$requete->fetchAll(PDO::FETCH_ASSOC);
		 $NoMaj = True;
		 echo( "<p style=color:#778899;> Veuillez installer l'un des logiciels ci-dessous pour remplacer $nom qui est obsolète.</p>");
		 foreach($tab as $cle => $val)
		 {
            foreach($val as $cle1 =>$val1)
			{
				$nomVersion  = substr($val1,-1);
				if ( $nomVersion > $numerosVersion )//Si il existe un numéros de version supérieur à celle actuel.
				{
					echo( "<p> <center> <bold style=color:#778899;> Voici les versions de $val1 qui sont compatibles avec votre ancienne version de $nom : </bold></center> </p>");
					 $NoMaj = False;
					 $tabLogicielNonObsolete = LogicielDisponible($val1,$today,$se ); 
					 
					  $i = 0;
					   echo ("<table style=color:#778899;> <tr >
						   <th>Product_Version </th>
						   <th>dataBase_Version </th>
						   <th>Operating_System </th>
						   <th>Database_Supported_Until</th>
						   <th>Scope_Supported_Until</th>
						   <th>Operating_System_Supported_Until</th>
						   </tr>");
					  foreach($tabLogicielNonObsolete as $cle => $val)
					  {
						
						echo("<tr><td> $val[Product_Version]</td>");
						echo("<td > $val[dataBase_Version]</td>");
						echo("<td > $val[Operating_System]</td>");
						echo("<td > $val[Database_Supported_Until]</td>");
						echo("<td > $val[Scope_Supported_Until]</td>");
						echo("<td > $val[Operating_System_Supported_Until]</td><tr>");
						
						
					  }
					 
					  echo("</table> </br>");
					  
				}
				
							
			}
		 }
		 if ($NoMaj)
		{
			echo(" il n'y a pas encore de nouvellles mises à jours ");
		}
	}
	
	
	/**
          * Cette fonction va permettre de récupérer les logiciels qu'on va pouvoir télécharger à la palce de l'ancien.
		  * @param $tableName: Le nom de la table.
		  * @param $date: La date d'aujourd hui.
		  * @param $se: Le nom du Systeme d'exploitation.
		 	  
	*/
	
	function LogicielDisponible($tableName,$date,$se )
	{
		 
		 $bd=new PDO("mysql:host=localhost;dbname=mises_a_jours","root","");
		 $bd->query('SET NAMES utf8');
         $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		 $requete=$bd->prepare("SELECT distinct Product_Version,dataBase_Version,Operating_System, Database_Supported_Until, Scope_Supported_Until, Operating_System_Supported_Until FROM $tableName where Database_Supported_Until > :today  and Operating_System = :SystemE and Scope_Supported_Until > :today and Operating_System_Supported_Until > :today");	
		 $requete->bindValue(':today',$date);
		 $requete->bindValue(':SystemE',$se);
		 $requete->execute();
         $tab=$requete->fetchAll(PDO::FETCH_ASSOC);
		 return $tab;
	}


?>