import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { SingolaConsegnaPage } from './singola-consegna.page';

const routes: Routes = [
  {
    path: '',
    component: SingolaConsegnaPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [SingolaConsegnaPage]
})
export class SingolaConsegnaPageModule {}
