import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ElencoConsegnePage } from './elenco-consegne.page';

const routes: Routes = [
  {
    path: '',
    component: ElencoConsegnePage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ElencoConsegnePage]
})
export class ElencoConsegnePageModule {}
