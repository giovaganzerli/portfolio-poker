import { TestBed } from '@angular/core/testing';

import { SqliteServicesService } from './sqlite-services.service';

describe('SqliteServicesService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: SqliteServicesService = TestBed.get(SqliteServicesService);
    expect(service).toBeTruthy();
  });
});
