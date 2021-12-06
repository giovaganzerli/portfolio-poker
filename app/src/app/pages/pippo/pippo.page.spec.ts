import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PippoPage } from './pippo.page';

describe('PippoPage', () => {
  let component: PippoPage;
  let fixture: ComponentFixture<PippoPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PippoPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PippoPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
