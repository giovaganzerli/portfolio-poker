import { Component, OnInit } from '@angular/core';

import { SqliteServicesService } from '../../services/sqlite-services.service'

@Component({
  selector: 'app-impostazioni',
  templateUrl: './impostazioni.page.html',
  styleUrls: ['./impostazioni.page.scss'],
})
export class ImpostazioniPage implements OnInit {
  private conta: number;

  constructor(
      private SqLiteFunc : SqliteServicesService
  ) { }

  ngOnInit() {
    this.conta = 1;
    this.SqLiteFunc.createDB();
  }

  inserisci(){
    this.SqLiteFunc.insertIntoConsegne(this.conta, 'Contenuto prova');
    this.conta ++;
  }

  seleziona(){
    this.SqLiteFunc.selectAllConsegne(function (rows) {
      console.log(rows);
      for(let i=0; i<rows.length; i++){
        console.log(rows.item(i));
        // console.log(rows.item(i).contenuto);
      }
    });
  }

  selezionaById(){
    this.conta --;
    this.SqLiteFunc.selectConsegnaById(this.conta, function (rows) {
      console.log(rows);
      for(let i=0; i<rows.length; i++){
        console.log(rows.item(i));
      }
    });
    this.conta ++;
  }

  update(){
    this.conta --;
    this.SqLiteFunc.updateConsegnaById(this.conta, 'Altro contenuto');
    this.conta ++;
  }

  cancella(){
    this.SqLiteFunc.clearTableConsegne();
  }

  cancellaById(){
    this.conta --;
    this.SqLiteFunc.deleteConsegnaById(this.conta);
    this.conta --;
  }

}
