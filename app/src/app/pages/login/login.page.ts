import { Component, OnInit } from '@angular/core';
import { ChiamateApiService } from '../../services/chiamate-api.service';
import { NativeStorage } from '@ionic-native/native-storage/ngx';
import { NavController } from '@ionic/angular';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
})
export class LoginPage implements OnInit {
  private params: any;

  constructor(
    private chiamateApi: ChiamateApiService,
    private localStorage: NativeStorage,
    private navCtrl: NavController
  ) { }

  ngOnInit() {
      this.localStorage.getItem('user').then(
          (success) => {
              if(success.logged){
                  this.navCtrl.navigateRoot('/home');
              }
          },
          error => console.error(error)
      );

    this.params = {
      username: '',
      password: ''
    };
  }

  eseguiLogin(){
    let $this = this;

    this.chiamateApi.userLogin__post(this.params,
      function(data) {
        // console.log('success!');
        // console.log(data);
        let user = {
          logged: true,
          tokens: { session: data.token_session, auth: data.token_auth }
        };

        $this.localStorage.setItem('user', user)
          .then(
            (data) => {
              console.log("settato!", data);
              $this.navCtrl.navigateRoot('/home/elenco-consegne');
            },
            (error) => console.error('Error storing item', error)
          );
      },
      function(error) {
        console.log('error userLogin__post');
        console.log(error);
      });
  }

  chiamataProva(){
    let par = {
      fields:{
        codice_agente: 'cod agente',
        'allegato-pdf': "allegato pdf",
        'allegato-firma': "allegato firma"
      }
    };

    this.chiamateApi.sendPostRequest__post(par,
      function (data){
          console.log(data);
      },
      function (error) {
          console.log(error);
      });
  }

}
