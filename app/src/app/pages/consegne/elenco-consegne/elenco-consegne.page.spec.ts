import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ElencoConsegnePage } from './elenco-consegne.page';

describe('ElencoConsegnePage', () => {
  let component: ElencoConsegnePage;
  let fixture: ComponentFixture<ElencoConsegnePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ElencoConsegnePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ElencoConsegnePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
