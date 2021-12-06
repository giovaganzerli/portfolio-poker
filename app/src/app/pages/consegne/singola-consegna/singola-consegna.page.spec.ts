import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SingolaConsegnaPage } from './singola-consegna.page';

describe('SingolaConsegnaPage', () => {
  let component: SingolaConsegnaPage;
  let fixture: ComponentFixture<SingolaConsegnaPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SingolaConsegnaPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SingolaConsegnaPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
