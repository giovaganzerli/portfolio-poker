import { Component, OnInit, ViewChild  } from '@angular/core';
import SignaturePad from '../../librerie/signature_pad/signature_pad';
import { IonReorderGroup, ToastController } from '@ionic/angular';
import { ChiamateApiService } from '../../services/chiamate-api.service'

@Component({
  selector: 'app-pippo',
  templateUrl: './pippo.page.html',
  styleUrls: ['./pippo.page.scss'],
})
export class PippoPage implements OnInit {
  private canvas;
  private signaturePad;
  private variabile: any;
  private arr: any;
  // @ts-ignore
  @ViewChild(IonReorderGroup) reorderGroup: IonReorderGroup;

  constructor(
      public toastController: ToastController,
      private chiamateApi: ChiamateApiService
  ) { }

  ngOnInit() {
    this.canvas = document.getElementById('pad-firma');
    this.signaturePad = new SignaturePad(this.canvas);

    this.variabile = "prova";
    this.arr = ['primo', 'secondo', 'terzo', 'quarto', 'quinto'];

    let params = {
      primo: 'prova',
      secondo: 'test'
    };

    this.chiamateApi.chiamataTest__get('', params,
        function(data) {
          console.log('success!');
          console.log(data);
        },
        function(error) {
          console.log('error :(');
          console.log(error);
        });
  }

  pulisciPad(){
    this.signaturePad.clear();
  }

  salvaFirma(){
    let firma = this.signaturePad.toDataURL();
    console.log(firma);
  }

  toggleReorder(){
    this.reorderGroup.disabled = !this.reorderGroup.disabled;
  }

  reorderFunc(event: any){
    console.log(event);
    console.log(this.arr);
    this.arr = event.detail.complete(this.arr);
    console.log(this.arr);
    this.visualizzaToast('Fatto!');
  }

  async visualizzaToast(testo){
    const toast = await this.toastController.create({
      message: testo,
      duration: 2000
    });
    toast.present();
  }

}
