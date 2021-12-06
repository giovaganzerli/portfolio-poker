import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SingolaScadenzaPage } from './singola-scadenza.page';

describe('SingolaScadenzaPage', () => {
  let component: SingolaScadenzaPage;
  let fixture: ComponentFixture<SingolaScadenzaPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SingolaScadenzaPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SingolaScadenzaPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
