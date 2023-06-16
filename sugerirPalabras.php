<!--
	Autores:
	Alvarez Carbajal Jonatan
	Beltrán Orozco Isaac
	Cruz Contreras Karen Tiffany
	Escamilla Sanchez Alejandro

	Grupo:
	3CV4
-->

<style>
	td, th {text-align: left; padding: 8px;}
</style>
<body>
<form action="sugerirPalabras.php" method="post">
	<input name="text" type="text" placeholder="Palabra a buscar" pattern="[a-zA-ZñÑáÁàÀäÄéÉèÈëËíÍìÌïÏóÓòÒöÖúÚùÙüÜ]{1,}">
	<input type="submit" name="Buscar" value="Buscar">

<?php
	#Variables "Globales".
	#Modificalas para calibrar la certeza de los resultados.
	$LIM_POSICION = 2;
	$LIM_MALARACHA = 3; #LIM_MALARACHA = LIM_TAMAÑO?
	$LIM_TAMAÑO = 4; 
	$MIN_PORCENTAJE = 0.8;

	#Abrimos nuestro archivo Diccionario.txt.
	#En el diccionario solo debe aparecer una palabra por linea.
	$ruta = "DiccionarioGrande.txt";
	$archivo = fopen($ruta, "r");

	#Si el boton Buscar es presionado
	if(isset($_POST['Buscar']))
	{
		#Obtenemos la palabra ingresada y la guardamos, tambien a su tamaño.
		$buscar = $_POST['text'];
		$bus_tam = strlen($buscar);

		#Imprimimos la palabra buscada y el inicio de nuestra tabla de sugerencias.
		echo "<p>Palabra buscada: <b>$buscar</b></p>";
		echo "<table><tr><th><b>Sugerencia:</b></th><th><b>Coincidencia:</b></th></tr>";

		#Leemos el diccionario.
		#Mientras el archivo no termine de leerse.
		$i=0;
		while(!feof($archivo))
		{
			#Guardamos la palabra de esta linea del Diccionario y nos movemos a la siguiente linea.
			$revisando = fgets($archivo);
			#Usar strlen() en un string generado por fgets() produce inconsistencias en el tamaño,
			#por ello depuraremos dicho string mediante str_word_count.
			$array_aux = str_word_count($revisando, 1, "ñÑáÁàÀäÄéÉèÈëËíÍìÌïÏóÓòÒöÖúÚùÙüÜ");
			$revisando = $array_aux[0];

			#Calculamos la diferencia de tamaño entre la palabra buscada y la palabra revisada.
			$rev_tam = strlen($revisando);
			$dif_tam = $rev_tam-$bus_tam;

			#Si la palabra del diccionario NO difiere en tamaño por mas de LIM_TAMAÑO con la palabra buscada.
			if(abs($dif_tam) < $LIM_TAMAÑO)
			{
				#Guardamos la palabra revisada en la lista de sugerencias.
				$sugerencias[$i] = $revisando;

				#Generaremos un "array paralelo" a "sugerencias",
				#el cual contendra los atributos importantes de las palabras ahi guardadas
				#como tamaño, "parecido" y "malaracha".
				$atrib[$i]["tam"] = $rev_tam;
				$atrib[$i]["par"] = 0;
				$atrib[$i]["mr"] = 0;

				#Si la palabra revisada es mas grande que la que buscamos.
				if($dif_tam > 0)
					#Sumamos esa diferencia de tamaño a "malaracha".
					$atrib[$i]["mr"] = $dif_tam;

				#Aunmentamos el indice para poder agregar otra palabra a nuestra lista de sugerencias.
				$i++;
			}
		}
		fclose($archivo);

		#Por cada letra en la palabra buscada.
		for($i=0; $i<$bus_tam; $i++)
		{
			#Por cada palabra en "sugerencias".
			for($j=0; $j<count($sugerencias); $j++)
			{
				#Desactivamos las notificaciones de variables indefinidas porque en el siguiente if preguntaremos por ubicaciones que pueden salirse de los arreglos a tratar, lo que normalmente imprimiria un error.
				error_reporting(E_ALL ^ E_NOTICE);

				#Si la letra revisada en este momento en la palabra "buscar" esta en la misma posicion en la palabra del diccionario.
				if($sugerencias[$j][$i] != null && $buscar[$i]==$sugerencias[$j][$i])
				{
					#Aumentamos el "parecido" de esta palabra, indicamos que la letra ya fue calificada, y disminuimos la "malaracha", si esta no es un cero ya de por si. 
					$atrib[$j]["par"]++;
					$atrib[$j]["cal".$i] = 1;
					if($atrib[$j]["mr"] != 0)
						$atrib[$j]["mr"]--; 
				}
				else
				{
					#Por todas las posiciones que podemos revisar dentro de LIM_POSICION.
					for($k=1; $k<=$LIM_POSICION; $k++)
					{
						#Si la posicion que solicitamos existe.
						#Y la letra que estamos revisando en "buscar" es igual a la que esta en la posicion solicitada de la palabra "sugerencias[$j]".
						#Y la letra no ha sido calificada anteriormente.
						if( ($sugerencias[$j][$i-$k] != null) && ($buscar[$i] == $sugerencias[$j][$i-$k]) && ($atrib[$j]["cal".($i-$k)] == null) )
						{
							#Sumamos una cantidad decimal a el "parecido" de esta palabra, entre mas lejos de la posicion original estemos menor sera esa puntuacion.
							#Marcamos que ya calificamos esta letra.
							#Y dejamos de revisar mas posiciones para comparar esta letra.
							$atrib[$j]["par"] = $atrib[$j]["par"] + ( ($LIM_POSICION+2-$k)/($LIM_POSICION+2) );
							$atrib[$j]["cal".($i-$k)] = 1;
							break;
						}
						#Lo mismo que en el if anterior pero ahora en el lado en la posicion de adelante.
						elseif( ($sugerencias[$j][$i+$k] != null) && ($buscar[$i] == $sugerencias[$j][$i+$k]) && ($atrib[$j]["cal".($i+$k)] == null) )
						{
							$atrib[$j]["par"] = $atrib[$j]["par"] + ( ($LIM_POSICION+2-$k)/($LIM_POSICION+2) );
							$atrib[$j]["cal".($i+$k)] = 1;
							break;
						}
						#Si ya hemos terminado de revisar todo el rango permitido por LIM_POSICION.
						elseif($k==($LIM_POSICION-1))
						{
							#Se entiende que la letra simplemente no coincidio, por lo que no aumentamos el "parecido" de la palabra, pero si la "malaracha".
							$atrib[$j]["mr"]++;

							#Pero, si vemos que en la posicion original de esta letra no hay nada.
							if($sugerencias[$j][$i] == null)
								#Entendemos que a la palabra buscada le sobran letras, lo cual vemos mas preciso que a que le falten letras, por ello le subiremos un poco el "parecido" a la palabra del diccionario en cuestion.
								$atrib[$j]["par"] = $atrib[$j]["par"] - ( ($LIM_POSICION+1)/($LIM_POSICION+2) );
						}
					}
				}
				#Reactivamos todas la notificaciones de errores.
				error_reporting(E_ALL);

				#Si la "malaracha" de esta palabra supera el limite o
				#Esta es la ultima letra que revisaremos de la palabra buscada y el "porcentaje de parecido" resultante de toda la revision no supera a MIN_PORCENTAJE.
				if( ($atrib[$j]["mr"] >= $LIM_MALARACHA) || (($i == $bus_tam-1) && (($atrib[$j]["par"]/$atrib[$j]["tam"]) < $MIN_PORCENTAJE)) )
				{
					#Entonces eliminamos la palabra de la lista de sugerencias.
					#Reordenamos la lista para mantener la coherencia con los indices.
					#Y volvemos a revisar la palabra de la posicion $j actual en sugerencias, puesto que al reindexar, ahora sugerencias[$j] es diferente a la que acabamos de revisar.
					unset($sugerencias[$j], $atrib[$j]);
					$sugerencias = array_values($sugerencias);
					$atrib = array_values($atrib);
					$j--;
				}
			}
		}



		for($i=0; $i<count($sugerencias); $i++)
		{
			echo "<tr><td>".$sugerencias[$i]."</td><td>".(100*$atrib[$i]["par"]/$atrib[$i]["tam"])."%</td></tr>";
		}
		echo "</table>";


	}
?>

</form>