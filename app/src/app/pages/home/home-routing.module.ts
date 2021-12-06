import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { HomePage } from './home.page';

const routes: Routes = [
  {
    path: '',
    component: HomePage,
    children: [
      {
        path: 'elenco-consegne',
        children: [
          {
            path: '',
            loadChildren: () =>
              import('../consegne/elenco-consegne/elenco-consegne.module').then(m => m.ElencoConsegnePageModule)
          }
        ]
      },
      {
        path: 'elenco-scadenze',
        children: [
          {
            path: '',
            loadChildren: () =>
              import('../scadenze/elenco-scadenze/elenco-scadenze.module').then(m => m.ElencoScadenzePageModule)
          }
        ]
      },
      {
        path: 'impostazioni',
        children: [
          {
            path: '',
            loadChildren: () =>
              import('../impostazioni/impostazioni.module').then(m => m.ImpostazioniPageModule)
          }
        ]
      },
      {
        path: '',
        redirectTo: '/home/elenco-consegne',
        pathMatch: 'full'
      }
    ]
  },
  {
    path: 'home',
    redirectTo: '/home/elenco-consegne',
    pathMatch: 'full'
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class HomePageRoutingModule {}
