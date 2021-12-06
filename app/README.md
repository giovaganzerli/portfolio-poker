# TEST IONIC ANGULAR

##TODO:
* controllare se va bene commentare "import Platform = NodeJS.Platform;" nel file app.component.ts
* controllare se nelle pagine va inserito anche il file component.ts

### Si installa con
$ionic start test-ionic-angular sidemenu

### Componenti Ionic
https://ionicframework.com/docs/intro/concepts

* Module: sono i moduli di Angular?
https://angular.io/guide/architecture-modules
Il modulo collega il controller con la view, è quì che vanno inserite le dipendenze
* Page: https://ionicframework.com/docs/v3/api/navigation/IonicPage/
* Component: sono i componenti tipo i bottoni, le liste, le card ecc.
* Service: i service sono tipo le factory, "collezioni di funzioni"
iniettabili negli altri moduli per favorire il riutilizzo del codice https://www.joshmorony.com/when-to-use-providersservicesinjectables-in-ionic/
* Class: è un componente generale?
* Directive: A directive is basically a component without a template.
The basic idea is that you would create a custom component to create some new element in your application and you would create a directive to change the behaviour of some existing component
https://www.joshmorony.com/using-a-directive-to-modify-the-behaviour-of-an-ionic-component/
https://www.joshmorony.com/how-to-create-a-directive-in-ionic-2-parallax-header/
* Guard:
* Pipe:
* Interface:
* Enum:

#### Comando generate:
* ionic generate page pippo:
nella root dell'app (alla stessa altezza di home) genera una cartella "pippo" con tutti i file
necessari: 'module.ts', 'page.html', 'page.scss', 'page.spec.ts', 'page.ts'.
<br>
Viene aggiunto in automatico il routing path dentro al file 'app-routing.module.ts'
* ionic generate module pippo:
nella root dell'app (alla stessa altezza di home) genera una cartella "pippo" contenente il file
'module.ts'
* ionic generate component myComp:
nella root genera una cartella myComp con i file:
'component.html', 'component.scss', 'component.spec.ts', 'component.ts'
* ionic generate service services/myServ:
crea una cartella services con dentro i file 'service.spec.ts', 'service.ts'.
Se non viene specificata la cartella services i file vengono messi dentro alla root, credo che
sia cosa buona e giusta metterli in una cartella per non fare troppa confusione
* ionic generate directive myDir:
crea i file 'directive.spec.ts' e 'directive.ts', anche quì mette i file nella root quindi è meglio
inserirli in una cartella per tenere tutto ordinato.

### Chiamate API
* this.http.get('url richiesta', {params: params}) -> la chiamata GET viene automaticamente
composta con i parametri
* this.http.post('url richiesta', params) -> in questo caso params è il body, quindi nelle POST
basta passargli i parametri per inserirli nel body
* controllare questo https://angular.io/guide/http#requesting-a-typed-response e le "interface"
per le risposte tipizzate 

### Navigazione tra le pagine:
Includere import { NavController } from '@ionic/angular';
Inserire nel construct private navCtrl: NavController
Usare i comandi:
* this.navCtrl.navigateForward('/route');
* this.navCtrl.navigateBack('/route');
* this.navCtrl.navigateRoot('/route');

### Note su Typescript e ECS6
#### export:
Se in un file c'è "export class SignaturePad" quando lo importo in un altro controller devo
scrivere "import { SignaturePad } from ...".<br>
Se invece c'è "export default class SignaturePad" non devo mettere le graffe nell'inclusione:
"import SignaturePad from ...". Questo perchè è stato settato come "default" da esportare