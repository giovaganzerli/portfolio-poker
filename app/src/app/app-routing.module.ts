import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';

const routes: Routes = [
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full'
  },
  {
    path: 'home',
    loadChildren: () => import('./pages/home/home.module').then(m => m.HomePageModule)
  },
  {
    path: 'pippo',
    loadChildren: () => import('./pages/pippo/pippo.module').then(m => m.PippoPageModule)
  },
  {
    path: 'login',
    loadChildren: () => import('./pages/login/login.module').then(m => m.LoginPageModule)
  },
  {
    path: 'singola-scadenza',
    loadChildren: () => import('./pages/scadenze/singola-scadenza/singola-scadenza.module').then(m => m.SingolaScadenzaPageModule)
  },
  {
    path: 'singola-consegna',
    loadChildren: () => import('./pages/consegne/singola-consegna/singola-consegna.module').then(m => m.SingolaConsegnaPageModule)
  }

];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules })
  ],
  exports: [RouterModule]
})
export class AppRoutingModule {}
