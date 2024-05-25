<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         * Categories are:
         * 1. Ciencia Ficción
         * 2. Fantasía
         * 3. Terror
         * 4. Romance
         * 5. Aventura
         * 6. Misterio
         * 7. Biografía
         * 8. Historia
         * 9. Política
         * 10. Economía
         * 11. Autoayuda
         * 12. Cocina
         * 13. Infantil
         * 14. Juvenil
         * 15. Poesía
         * 16. Ensayo
         * 17. Religión
         * 18. Filosofía
         * 19. Ciencia
         * 20. Tecnología
         * 21. Arte
         * 22. Música
         * 23. Deportes
         *
         * Authors are:
         * 1. Arthur Conan Doyle
         * 2. Isaac Asimov
         * 3. J. R. R. Tolkien
         * 4. Stephen King
         * 5. Jane Austen
         * 6. Jules Verne
         * 7. Agatha Christie
         * 8. Gabriel García Márquez
         * 9. George Orwell
         * 10. Friedrich Nietzsche
         * 11. Dale Carnegie
         * 12. Julia Child
         * 13. Roald Dahl
         * 14. J. K. Rowling
         * 15. Brandon Sanderson
         */
        $books = [
            [
                'name' => 'Estudio en escarlata',
                'description' => <<<EOT
                Estudio en escarlata es la primera novela de Sherlock Holmes escrita por Sir Arthur Conan Doyle. Publicada en 1887, la obra es una de las más conocidas del detective y su fiel compañero, el doctor Watson. En ella, los dos personajes se conocen y comienzan a compartir piso en el 221B de Baker Street, Londres. La historia comienza con el encuentro entre Holmes y Watson, que se convierten en compañeros de piso y amigos. Pronto, el detective se ve envuelto en un caso de asesinato que le llevará a conocer a su archienemigo, el profesor Moriarty.
                EOT
                ,
                'image_path' => 'https://imagessl3.casadellibro.com/a/l/s7/13/9788491053613.webp',
                'authors' => [1],
                'categories' => [6],
            ],
            [
                'name' => 'El señor de los anillos',
                'description' => <<<EOT
                El Señor de los Anillos es una novela de fantasía épica escrita por J. R. R. Tolkien. Publicada en tres volúmenes entre 1954 y 1955, la obra es una de las más conocidas y leídas de la literatura fantástica. En ella, se narra la historia de la Tierra Media y de sus habitantes, los hobbits, elfos, enanos y hombres. La trama sigue a Frodo Bolsón, un hobbit que debe llevar un anillo mágico al Monte del Destino para destruirlo y evitar que caiga en manos del Señor Oscuro, Sauron.
                EOT
                ,
                'image_path' => 'https://imagessl7.casadellibro.com/a/l/s7/57/9788445018057.webp',
                'authors' => [3],
                'categories' => [2],
            ],
            [
                'name' => 'Las aventuras de Sherlock Holmes',
                'description' => <<<EOT
                En los doce relatos recogidos en este libro, Sherlock Holmes despliega toda su genialidad y potencial de razonamiento y, junto a Watson, formancuna de las parejas más célebres de la ficción.
                En Escándalo en Bohemia, el rey de Bohemia solicita la ayuda de Sherlock Holmes para recuperar una imagen que puede usarse en su contra. En La liga de los Pelirrojos, un hombre acude al detective en busca de consejo sobre la desaparición de una extraña sociedad de pelirrojos. En El misterio de Boscombe Valley, Holmes investiga el asesinato de un hombre cuyo hijo es el principal sospechoso. EnLas cinco semillas de naranja, los detectives se enfrentan a un caso familiar: dos hermanos y un hijo reciben cartas del KKK que contienen cinco pepitas de naranja, un presagio de la muerte. En La banda de lunares, Helen Stoner ruega a Sherlock Holmes que le ayude a resolver la muerte de su hermana, fallecida un día antes de su boda. Ahora la mujer teme correr la misma suerte.
                EOT,
                'image_path' => 'https://imagessl6.casadellibro.com/a/l/s7/16/9788408255116.webp',
                'authors' => [1],
                'categories' => [6],
            ],
            [
                'name' => 'Trilogía Fundación',
                'description' => <<<EOT
                ¿Por que surgen y caen los imperios? La Trilogía Fundación es una  historia con múltiples capas cuya lectura sigue siendo tan apasionante  hoy como cuando se publicó por vez primera en la decada de 1950.  Incorporando infinitos elementos de la política contemporánea, la  historia antigua y las matemáticas, la obra maestra de Asimov explora el  declive del Imperio Galáctico, una civilización que gobernó sobre casi veinticinco millones de planetas habitados.
                EOT,
                'image_path' => 'https://imagessl2.casadellibro.com/a/l/s7/42/9788418037542.webp',
                'authors' => [2],
                'categories' => [1],
            ],
            [
                'name' => 'El camino de los reyes',
                'description' => <<<EOT
                Anhelo los días previos a la Última Desolación.

                Los días en que los Heraldos nos abandonaron y los Caballeros Radiantes se giraron en nuestra contra. Un tiempo en que aún había magia en el mundo y honor en el corazón de los hombres.

                El mundo fue nuestro, pero lo perdimos. Probablemente no hay nada más estimulante para las almas de los hombres que la victoria.

                ¿O tal vez fue la victoria una ilusión durante todo ese tiempo? ¿Comprendieron nuestros enemigos que cuanto más duramente luchaban, más resistíamos nosotros? Quizá vieron que el fuego y el martillo tan solo producían mejores espadas. Pero ignoraron el acero durante el tiempo suficiente para oxidarse.

                Hay cuatro personas a las que observamos. La primera es el médico, quien dejó de curar para convertirse en soldado durante la guerra más brutal de nuestro tiempo. La segunda es el asesino, un homicida que llora siempre que mata. La tercera es la mentirosa, una joven que viste un manto de erudita sobre un corazón de ladrona. Por último está el alto príncipe, un guerrero que mira al pasado mientras languidece su sed de guerra.

                El mundo puede cambiar. La potenciación y el uso de las esquirlas pueden aparecer de nuevo, la magia de los días pasados puede volver a ser nuestra. Esas cuatro personas son la clave.
                EOT,
                'image_path' => 'https://imagessl2.casadellibro.com/a/l/s7/62/9788466657662.webp',
                'authors' => [15],
                'categories' => [2],
            ]
        ];

        foreach ($books as $book) {
            $authors = $book['authors'];
            unset($book['authors']);
            $categories = $book['categories'];
            unset($book['categories']);
            $book = Book::create($book);
            $book->authors()->attach($authors);
            $book->categories()->attach($categories);
        }
    }
}
