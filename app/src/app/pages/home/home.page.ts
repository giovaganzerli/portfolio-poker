import { Component, ViewChild, OnInit } from '@angular/core';
import { IonTabs } from '@ionic/angular';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
})
export class HomePage implements OnInit{
  // @ts-ignore
  @ViewChild(IonTabs) tabs: IonTabs;
  private nomePagina: string;

  constructor() {}

  ngOnInit(){}

  doSomething(){
    this.nomePagina = this.tabs.getSelected().replace('-', ' ');
  }

}
