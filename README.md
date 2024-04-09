Адвантика редирект для SEO. v1.0

Модуль создан для работы:
1) Со списками редиректов.
2) Удаление множественных слешей.
3) Редирект с 404 страниц.
4) Редирект со страниц без слеша на слеш.
5) Редирект с http на https и на оборот.
6) Редирект с www и без www.
7) Редирект со страниц */index.php на */.
8) Преобразование URL в нижний регистр.
9) Настройка редиректов с инфоблоков.

Что делает редирект инфоблоков?<br>
Редирект с изменением елемента и раздела секций инфоблока<br>
Переводит параметры ЧПУ<br>
Можно делать по нескольким инфоблокам сразу.<br>
с #SECTION_CODE# на #SECTION_CODE_PATH#<br>
с #SECTION_CODE_PATH# на #SECTION_CODE#<br>
с #ELEMENT_CODE# на #ELEMENT_ID#<br>
с #ELEMENT_ID# на #ELEMENT_CODE#<br>

Как работать со списком редиректов?<br>
Чтобы работать со списком редиректов нужно:<br>
1. Зайти в административную панель сайта.<br>
2. Нажимаем настройки 
3. Настройки продкута -> Сайты -> Список сайтов.
4. Нажимаем правой кнопкой мыши на нужный нам сайт и выбираем "Адвантика: Редиректы для SEO - Список"
5. Открывается модельное окно "Настройка URL редиректов для сайта"
6. Сверху есть кнопка "Загрузить файл", Если кликнуть по данной кнопке откроется окно где можно будет загрузить список редиректов в формате .csv
7. Пример файла со списками лежит в репозитории ".advantika.redirect.urls.s1.csv".
8. Также сушествует возможность самостоятельно создать запись редиректа, для этого нужно в поле откуда ввести относительный адрес с какой страницы вы хотите произвести редирект, после в поле куда ввести ссылку куда должен редирект перенаправить. Также сушествует возможность выбрать тип редиректа "301" или "302". Если вы совершили ошибку или данный редирект вам больше не нужен вы можете его удали выставив галку напротив нужного вам редиректа и нажать на кнопку применить изменения.

Что делает редирект с 404 страницы?<br>
Данная функция Просматриваем запрашиваемую страницу у пользователя и если данная страница отдает статаус "404", то она автоматически перенаправляет пользователя на предыдуший раздел.<br>
Пример:<br>
Зашли по ссылке "/services/uslugi-tsentra-krasoty-rif/esteticheskaya-kosmetologiya/chistka-litsa/вывфывфыв" и данная страница выдала 404<br>
Модуль поймёт, что страница отдала 404 и переадресует на раздел ниже "/services/uslugi-tsentra-krasoty-rif/esteticheskaya-kosmetologiya/chistka-litsa/"<br>

Что делает редирект со страниц без слеша на слеш?<br>
Смотрит на окончание ссылки и если в конце данной ссылки не найден / то он его добавляет.<br>
Пример:<br>
"/catalog" -> "/catalog/"<br>

Что делает удаление множественных слешей?<br>
Название говрил само за себя. Смотрит на ссылку и если попадаются "///" подобного рода слеши престраивает ссылку и перенаправляет на неё.<br>
Пример:<br>
"//news///index.php" -> "/news/"<br>

Что делает редирект с http на https и на оборот?<br>
Изменяет протокол зашиты адресации<br>
Пример:<br>
"http://example.org" <-> "https://example.org"<br>

Что делает редирект с www и без www?<br>
Добавляет в начало домена www. или убирает его.<br>
Пример:<br>
"www.example.org" <-> "example.org"<br>

Что делает редирект со страниц */index.php на */?<br>
Смотрит на последный url ссылки и проверяет сушествует ли там index если есть то удаляет и перенапрвляет на сыылку без него.v
Пример:<br>
"/about/index.php" -> "/about/"<br>

Что делает преобразование URL в нижний регистр?<br>
Смотрит на ссылку и проверяет на наличие сымволов в верхнем регистре. Если такие есть то преобразует эту ссылку в нижний регистр и редиректит.<br>
Пример:<br>
"/services/uSlUgi-TsenTra-Krasoty-rif/EsteTicHeskaya-koSmetoLogiya/" -> "/services/uslugi-tsentra-krasoty-rif/esteticheskaya-kosmetologiya/"<br>

Также модуль в автоматическом режиме логирует список 404 страниц.<br>

____________________________________________________________________________________________________________________________________________________________________________________________________

Как установить модуль?

1. Скачиваем архив с модулем с 
GitHub https://github.com/ru1evka/modul_redirect_bitrix <br>
или <br>
Google Диск https://drive.google.com/file/d/10t4PUjhLTQ00avDyAlxPBr8OhiNaWei_/view?usp=sharing <br>
2. Распаковываем архив с модулем на сайте в папку "/bitrix/modules/"
3. В административной части сайта жмем "Установить".