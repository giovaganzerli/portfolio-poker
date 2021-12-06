import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ElencoScadenzePage } from './elenco-scadenze.page';

describe('ElencoScadenzePage', () => {
  let component: ElencoScadenzePage;
  let fixture: ComponentFixture<ElencoScadenzePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ElencoScadenzePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ElencoScadenzePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
