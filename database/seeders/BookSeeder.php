<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         * Genres are:
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
         * 6. Julio Verne
         * 7. Agatha Christie
         * 8. Gabriel García Márquez
         * 9. George Orwell
         * 10. Friedrich Nietzsche
         * 11. Dale Carnegie
         * 12. Julia Child
         * 13. Roald Dahl
         * 14. J. K. Rowling
         * 15. Brandon Sanderson
         * 16. Oscar Wilde
         * 17. Joel Dicker
         */
        $books = [
            [
                'title' => 'Estudio en escarlata',
                'description' => <<<'EOT'
                Estudio en escarlata es la primera novela de Sherlock Holmes escrita por Sir Arthur Conan Doyle. Publicada en 1887, la obra es una de las más conocidas del detective y su fiel compañero, el doctor Watson. En ella, los dos personajes se conocen y comienzan a compartir piso en el 221B de Baker Street, Londres. La historia comienza con el encuentro entre Holmes y Watson, que se convierten en compañeros de piso y amigos. Pronto, el detective se ve envuelto en un caso de asesinato que le llevará a conocer a su archienemigo, el profesor Moriarty.
                EOT,
                'cover_path' => 'https://imagessl3.casadellibro.com/a/l/s7/13/9788491053613.webp',
                'isbn' => '9788491053613',
                'publication_date' => '1887-11-01',
                'pages' => 224,
                'authors' => [1],
                'genres' => [6],
            ],
            [
                'title' => 'El señor de los anillos',
                'description' => <<<'EOT'
                El Señor de los Anillos es una novela de fantasía épica escrita por J. R. R. Tolkien. Publicada en tres volúmenes entre 1954 y 1955, la obra es una de las más conocidas y leídas de la literatura fantástica. En ella, se narra la historia de la Tierra Media y de sus habitantes, los hobbits, elfos, enanos y hombres. La trama sigue a Frodo Bolsón, un hobbit que debe llevar un anillo mágico al Monte del Destino para destruirlo y evitar que caiga en manos del Señor Oscuro, Sauron.
                EOT,
                'cover_path' => 'https://imagessl7.casadellibro.com/a/l/s7/57/9788445018057.webp',
                'isbn' => '9788445018057',
                'publication_date' => '1954-07-29',
                'pages' => 1272,
                'authors' => [3],
                'genres' => [2],
            ],
            [
                'title' => 'Las aventuras de Sherlock Holmes',
                'description' => <<<'EOT'
                En los doce relatos recogidos en este libro, Sherlock Holmes despliega toda su genialidad y potencial de razonamiento y, junto a Watson, formancuna de las parejas más célebres de la ficción.
                En Escándalo en Bohemia, el rey de Bohemia solicita la ayuda de Sherlock Holmes para recuperar una imagen que puede usarse en su contra. En La liga de los Pelirrojos, un hombre acude al detective en busca de consejo sobre la desaparición de una extraña sociedad de pelirrojos. En El misterio de Boscombe Valley, Holmes investiga el asesinato de un hombre cuyo hijo es el principal sospechoso. EnLas cinco semillas de naranja, los detectives se enfrentan a un caso familiar: dos hermanos y un hijo reciben cartas del KKK que contienen cinco pepitas de naranja, un presagio de la muerte. En La banda de lunares, Helen Stoner ruega a Sherlock Holmes que le ayude a resolver la muerte de su hermana, fallecida un día antes de su boda. Ahora la mujer teme correr la misma suerte.
                EOT,
                'cover_path' => 'https://imagessl6.casadellibro.com/a/l/s7/16/9788408255116.webp',
                'isbn' => '9788408255116',
                'publication_date' => '1892-10-14',
                'pages' => 384,
                'authors' => [1],
                'genres' => [6],
            ],
            [
                'title' => 'Trilogía Fundación',
                'description' => <<<'EOT'
                ¿Por que surgen y caen los imperios? La Trilogía Fundación es una  historia con múltiples capas cuya lectura sigue siendo tan apasionante  hoy como cuando se publicó por vez primera en la decada de 1950.  Incorporando infinitos elementos de la política contemporánea, la  historia antigua y las matemáticas, la obra maestra de Asimov explora el  declive del Imperio Galáctico, una civilización que gobernó sobre casi veinticinco millones de planetas habitados.
                EOT,
                'cover_path' => 'https://imagessl2.casadellibro.com/a/l/s7/42/9788418037542.webp',
                'isbn' => '9788418037542',
                'publication_date' => '1951-05-01',
                'pages' => 768,
                'authors' => [2],
                'genres' => [1],
            ],
            [
                'title' => 'El camino de los reyes',
                'description' => <<<'EOT'
                Anhelo los días previos a la Última Desolación.

                Los días en que los Heraldos nos abandonaron y los Caballeros Radiantes se giraron en nuestra contra. Un tiempo en que aún había magia en el mundo y honor en el corazón de los hombres.

                El mundo fue nuestro, pero lo perdimos. Probablemente no hay nada más estimulante para las almas de los hombres que la victoria.

                ¿O tal vez fue la victoria una ilusión durante todo ese tiempo? ¿Comprendieron nuestros enemigos que cuanto más duramente luchaban, más resistíamos nosotros? Quizá vieron que el fuego y el martillo tan solo producían mejores espadas. Pero ignoraron el acero durante el tiempo suficiente para oxidarse.

                Hay cuatro personas a las que observamos. La primera es el médico, quien dejó de curar para convertirse en soldado durante la guerra más brutal de nuestro tiempo. La segunda es el asesino, un homicida que llora siempre que mata. La tercera es la mentirosa, una joven que viste un manto de erudita sobre un corazón de ladrona. Por último está el alto príncipe, un guerrero que mira al pasado mientras languidece su sed de guerra.

                El mundo puede cambiar. La potenciación y el uso de las esquirlas pueden aparecer de nuevo, la magia de los días pasados puede volver a ser nuestra. Esas cuatro personas son la clave.
                EOT,
                'cover_path' => 'https://imagessl2.casadellibro.com/a/l/s7/62/9788466657662.webp',
                'isbn' => '9788466657662',
                'publication_date' => '2010-08-31',
                'pages' => 1200,
                'authors' => [15],
                'genres' => [2],
            ],
            [
                'title' => 'La isla misteriosa',
                'description' => <<<'EOT'
                Durante la guerra civil americana, cinco hombres logran escapar del  asedio de Richmond en un globo aerostático que finalmente acabará  estrellándose en una isla desierta de los Mares del Sur. Los cinco  compañeros no tienen nada salvo su ingenio para sobrevivir en una isla  que muy pronto se mostrará llena de secretos, misterios y enigmas que jamás hubieran podido imaginar.

                Jules Verne quizá lograra con La isla misteriosa su novela más  intrigante y entretenida. La presente edición, en magnífica traducción  de Teresa Clavel, se completa además con la introducción de Constantino  Bertolo, uno de los críticos literarios más prestigiosos de las letras hispánicas contemporáneas.
                EOT,
                'cover_path' => 'https://imagessl8.casadellibro.com/a/l/s7/48/9788491052548.webp',
                'isbn' => '9788491052548',
                'publication_date' => '1875-01-01',
                'pages' => 688,
                'authors' => [6],
                'genres' => [5],
            ],
            [
                'title' => '1984',
                'description' => <<<'EOT'
                En el año 1984 Londres es una ciudad lúgubre en la que la Policía del Pensamiento controla de forma asfixiante la vida de los ciudadanos. Winston Smith es un peón de este engranaje perverso y su cometido es reescribir la historia para adaptarla a lo que el Partido considera la versión oficial de los hechos. Hasta que decide replantearse la verdad del sistema que los gobierna y somete.
                EOT,
                'cover_path' => 'https://imagessl4.casadellibro.com/a/l/s7/44/9788499890944.webp',
                'isbn' => '9788499890944',
                'publication_date' => '1949-06-08',
                'pages' => 352,
                'authors' => [9],
                'genres' => [18],
            ],
            [
                'title' => 'Cien años de soledad',
                'description' => <<<'EOT'
                "Muchos años despues, frente al pelotón de fusilamiento, el coronel Aureliano Buendía había de recordar aquella tarde remota en que su padre lo llevó a conocer el hielo."

                Con esta cita comienza una de las novelas más importantes del siglo XX y una de las aventuras literarias más fascinantes de todos los tiempos. Millones de ejemplares de Cien años de soledad leídos en todas las lenguas y el premio Nobel de Literatura coronando una obra que se había abierto paso "boca a boca" -como gustaba decir el escritor- son la más palpable demostración de que la aventura fabulosa de la familia Buendía-Iguarán, con sus milagros, fantasías, obsesiones, tragedias, incestos, adulterios, rebeldías, descubrimientos y condenas, representaba al mismo tiempo el mito y la historia, la tragedia y el amor del mundo entero.
                EOT,
                'cover_path' => 'https://imagessl8.casadellibro.com/a/l/s7/08/9788497592208.webp',
                'isbn' => '9788497592208',
                'publication_date' => '1967-05-30',
                'pages' => 496,
                'authors' => [8],
                'genres' => [6],
            ],
            [
                'title' => 'Diez negritos',
                'description' => <<<'EOT'
                La novela más vendida de Agatha Christie, con unos 100 millones de ejemplares vendidos.
                Diez personas sin relación alguna entre sí son reunidas en un misterioso islote de la costa inglesa por un tal Sr. Owen, propietario de una lujosa mansión a la par que perfecto desconocido para todos sus invitados. Tras la primera cena, y sin haber conocido aún a su anfitrión, los diez comensales son acusados mediante una grabación de haber cometido un crimen en el pasado.

                Uno por uno, a partir de ese momento, son asesinados sin explicación ni motivo aparente. Sólo una vieja canción infantil parece encerrar el misterio de una creciente pesadilla.
                EOT,
                'cover_path' => 'https://imagessl0.casadellibro.com/a/l/s7/90/9788467045390.webp',
                'isbn' => '9788467045390',
                'publication_date' => '1939-11-06',
                'pages' => 256,
                'authors' => [7],
                'genres' => [6],
            ],
            [
                'title' => 'El retrato de Dorian Gray',
                'description' => <<<'EOT'
                Basil Hallward había terminado el retrato. El joven Dorian, al verlo, no  pudo más que desear, desde su frívola inocencia, que fuera su imagen la  que envejeciera y se corrompiera con el paso de los años mientras el  permanecía intacto. Y así fue: a partir de entonces, Dorian Gray  conservó no solo la lozanía y la hermosura propias de la juventud, sino el aspecto puro de los inocentes. Pero ¿a que precio?
                EOT,
                'cover_path' => 'https://imagessl6.casadellibro.com/a/l/s7/36/9788467032536.webp',
                'isbn' => '9788467032536',
                'publication_date' => '1890-06-20',
                'pages' => 288,
                'authors' => [16],
                'genres' => [6],
            ],
            [
                'title' => 'El libro de los Baltimore',
                'description' => <<<'EOT'
                Hasta que tuvo lugar el Drama existían dos ramas de la familia Goldman: los Goldman de Baltimore y los Goldman de Montclair. Los Montclair, de los que forma parte Marcus Goldman, autor de La verdad sobre el caso Harry Quebert, es una familia de clase media que vive en una pequeña casa en el estado de Nueva Jersey. Los Baltimore, prósperos y a los que la suerte siempre ha sonreído, habitan una lujosa mansión en un barrio de la alta sociedad de Baltimore.

                Ocho años después del Drama, Marcus Goldman pone el pasado bajo la lupa en busca de la verdad sobre el ocaso de la familia. Entre los recuerdos de su juventud revive la fascinación que sintió desde niño por los Baltimore, que encarnaban la América patricia con sus vacaciones en Miami y en los Hamptons y sus colegios elitistas. Con el paso de los años la brillante pátina de los Baltimore se desvanece al tiempo que el Drama se va perfilando. Hasta el día en el que todo cambia para siempre.
                EOT,
                'cover_path' => 'https://imagessl9.casadellibro.com/a/l/s7/39/9788420423739.webp',
                'isbn' => '9788420423739',
                'publication_date' => '2015-09-25',
                'pages' => 480,
                'authors' => [17],
                'genres' => [6],
            ],
        ];

        foreach ($books as $bookData) {
            /** @var list<int> $authors */
            $authors = $bookData['authors'];
            /** @var list<int> $genres */
            $genres = $bookData['genres'];
            unset($bookData['authors'], $bookData['genres']);

            $book = Book::create($bookData);
            $book->authors()->attach($authors);
            $book->genres()->attach($genres);
        }
    }
}
