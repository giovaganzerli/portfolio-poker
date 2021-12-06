import { Injectable } from '@angular/core';
import { SQLite, SQLiteObject } from '@ionic-native/sqlite/ngx';

@Injectable({
  providedIn: 'root'
})
export class SqliteServicesService {
  private dataBase: SQLiteObject;

  constructor(
      private sqlite: SQLite
  ) { }

  createDB(){
    this.sqlite.create({
      name: 'poker.db',
      location: 'default'
    })
      .then((db: SQLiteObject) => {
        this.dataBase = db;
        this.dataBase.executeSql('create table if not exists tableConsegne(id integer primary key, contenuto text)', [])
            .then((s) => console.log(s)).catch(e => console.log(e));

        this.dataBase.executeSql('create table if not exists tableScadenze(id integer primary key, contenuto text)', [])
            .then((s) => console.log(s)).catch(e => console.log(e));
      }
    ).catch(e => console.log(e));
  }

  openDB(){

  }

  // ===================== CONSEGNE =====================
  clearTableConsegne(){
    this.dataBase.executeSql('DELETE FROM tableConsegne', []).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('clearTableConsegne error: ' + error.message);
    });
  }

  deleteConsegnaById(id){
    this.dataBase.executeSql('DELETE FROM tableConsegne WHERE id = ?', [id]).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('deleteConsegnaById error: ' + error.message);
    });
  }

  insertIntoConsegne(id, contenuto){
    this.dataBase.executeSql('INSERT INTO tableConsegne (id, contenuto) VALUES (?, ?)', [id, contenuto]).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('insertIntoConsegne error: ' + error.message);
    });
  }

  selectAllConsegne(successCallback){
    this.dataBase.executeSql('SELECT * FROM tableConsegne', []).then( (resultSet) => {
      successCallback(resultSet.rows);
    }).catch( (error) => {
      console.log('selectAllConsegne error: ' + error.message);
    });
  }

  selectConsegnaById(id, successCallback){
    this.dataBase.executeSql('SELECT * FROM tableConsegne WHERE id = (?)', [id]).then( (resultSet) => {
      successCallback(resultSet.rows);
    }).catch( (error) => {
      console.log('selectConsegnaById error: ' + error.message);
    });
  }

  updateConsegnaById(id, contenuto){
    this.dataBase.executeSql('UPDATE tableConsegne SET contenuto = ? WHERE id = ?', [contenuto, id]).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('updateConsegnaById error: ' + error.message);
    });
  }

  // ===================== SCADENZE =====================
  clearTableScadenze(){
    this.dataBase.executeSql('DELETE FROM tableScadenze', []).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('clearTableScadenze error: ' + error.message);
    });
  }

  deleteScadenzaById(id){
    this.dataBase.executeSql('DELETE FROM tableScadenze WHERE id = ?', [id]).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('deleteScadenzaById error: ' + error.message);
    });
  }

  insertIntoScadenze(id, contenuto){
    this.dataBase.executeSql('INSERT INTO tableScadenze (id, contenuto) VALUES (?, ?)', [id, contenuto]).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('insertIntoScadenze error: ' + error.message);
    });
  }

  selectAllScadenze(successCallback){
    this.dataBase.executeSql('SELECT * FROM tableScadenze', []).then( (resultSet) => {
      successCallback(resultSet.rows);
    }).catch( (error) => {
      console.log('selectAllScadenze error: ' + error.message);
    });
  }

  selectScadenzaById(id, successCallback){
    this.dataBase.executeSql('SELECT * FROM tableScadenze WHERE id = (?)', [id]).then( (resultSet) => {
      successCallback(resultSet.rows);
    }).catch( (error) => {
      console.log('selectScadenzaById error: ' + error.message);
    });
  }

  updateScadenzaById(id, contenuto){
    this.dataBase.executeSql('UPDATE tableScadenze SET contenuto = ? WHERE id = ?', [contenuto, id]).then( (resultSet) => {
      console.log(resultSet);
    }).catch( (error) => {
      console.log('updateScadenzaById error: ' + error.message);
    });
  }

}
