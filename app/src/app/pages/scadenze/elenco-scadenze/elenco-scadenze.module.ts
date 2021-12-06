import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ElencoScadenzePage } from './elenco-scadenze.page';

const routes: Routes = [
  {
    path: '',
    component: ElencoScadenzePage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ElencoScadenzePage]
})
export class ElencoScadenzePageModule {}
