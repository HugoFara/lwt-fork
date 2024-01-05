import { expect } from 'chai';
import supertest from 'supertest';

const host = 'http://localhost'
const api_path = '/lwt/api.php/v1';

describe('Random API call', function() {
  it('expect a 400 without message', function(done) {
    supertest(host)
      .get(api_path)
      .expect(400)
      .expect('Content-Type', 'application/json', done)
  });

  it('expect a 400 and close', function(done) {
    supertest(host)
      .get(api_path)
      .expect(400)
      .expect('Content-Type', 'application/json')
      .end(function(err, res) {
        if (err) throw err;
        //console.log(res.body);
        done();
      });
  });
});


describe('Calls on GET', function() {

  it('GET /media-files', function(done) {
    supertest(host)
      .get(api_path + '/media-files')
      .expect('Content-Type', 'application/json')
      .expect(function(res) {
        expect(res.body.base_path).to.be.a('string');
      })
      .expect(200, done)
  });

  it('GET /phonetic-reading', function(done) {
    supertest(host)
      .get(api_path + '/phonetic-reading')
      .query({text: 'test', lgid: '1'})
      .expect('Content-Type', 'application/json')
      .expect(200, done)

    supertest(host)
      .get(api_path + '/phonetic-reading')
      .query({text: 'test', lang: 'en'})
      .expect('Content-Type', 'application/json')
      .expect(function(res) {
        expect(res.body.phonetic_reading).equal('test');
      })
      .expect(200, done)
  });

  it('GET /sentences-with-term, normal search', function(done) {
    supertest(host)
      .get(api_path + '/sentences-with-term/1')
      .query({lg_id: 1, word_lc: 'test'})
      .expect('Content-Type', 'application/json')
      .expect(200, done)
  });

  it('GET /sentences-with-term, advanced search', function(done) {
    supertest(host)
      .get(api_path + '/sentences-with-term/1')
      .query({lg_id: 1, word_lc: 'test', advanced_search: -1})
      .expect('Content-Type', 'application/json')
      .expect(200, done)
  });

  it('GET /sentences-with-term/{term-id}', function(done) {
    supertest(host)
      .get(api_path + '/sentences-with-term/1')
      .query({lg_id: 1, word_lc: 'test'})
      .expect('Content-Type', 'application/json')
      .expect(200, done)
  });

  it('GET /settings/theme-path', function(done) {
    supertest(host)
      .get(api_path + '/settings/theme-path')
      .query({path: 'css/styles.css'})
      .expect('Content-Type', 'application/json')
      .expect(200)
      .end((err, res) => {
        if (err) {
          return done(err);
        }
        
        // Check if the response contains the file name
        const filePath = res.body.theme_path;
        expect(filePath).to.match(/.+\/styles\.css$/);
        done();
      });
  });

  it('GET /terms/imported', function(done) {
    supertest(host)
      .get(api_path + '/terms/imported')
      .query({last_update : '', page: 0, count: 10})
      .expect('Content-Type', 'application/json')
      .expect(function(res) {
        expect(res.body.navigation).to.be.an.instanceof(Object);
        expect(res.body.terms).to.be.an.instanceof(Object);
        expect(res.body.navigation.current_page)
        .lessThanOrEqual(res.body.navigation.total_pages);
        expect(res.body.terms).instanceOf(Array);
      })
      .expect(200, done)
  });


  it('GET /terms/{term-id}/translations', function(done) {
    supertest(host)
      .get(api_path + '/terms/1/translations')
      .expect('Content-Type', 'application/json')
      .expect(function(res) {
        //console.log(res.body);
      })
      .expect(200, done)
  });

  
  it('GET /review/next-word', function(done) {
    supertest(host)
      .get(api_path + '/review/next-word')
      .query({
        'test_sql': '', 'word_mode': true, 'lg_id': 1,
        'word_regex': 'test', 'type': 0
      })
      //.expect('Content-Type', 'application/json')
      .expect(200, done)
  });

  it('GET /review/tomorrow-count', function(done) {
    supertest(host)
      .get(api_path + '/review/tomorrow-count')
      .query({
        'test_sql': ''
      })
      //.expect('Content-Type', 'application/json')
      .expect(200, done)
  });
  

  it('GET /texts/statistics', function(done) {
    supertest(host)
      .get(api_path + '/texts/statistics')
      .query({texts_id: '1,2'})
      .expect(function(res) {
        // with form 
        // total: [], expr: [], stat: [], totalu: [], expru: [], statu: []
      })
      .expect('Content-Type', 'application/json')
      .expect(200, done)
  });
  

  it('GET /version', function(done) {
    supertest(host)
      .get(api_path + '/version')
      .expect('Content-Type', 'application/json')
      .expect(200, done)
  });

});
