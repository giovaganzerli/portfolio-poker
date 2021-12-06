import { TestBed } from '@angular/core/testing';

import { ChiamateApiService } from './chiamate-api.service';

describe('ChiamateApiService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ChiamateApiService = TestBed.get(ChiamateApiService);
    expect(service).toBeTruthy();
  });
});
