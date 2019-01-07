<?php

// include_once('../data/lexicon_positif.php');

class Lexicon {

    public $kalimat; // menyimpan kalimat yang akan diproses
    private $dump = array(); // menyimpan detail proses 
    private $hasil;
    protected $lexicon_positif;
    protected $lexicon_negatif;
    protected $lexicon_negasi;

    public function __construct($kalimat = null) 
    {
        $json_lexicon_positif = file_get_contents( dirname(__FILE__) . DIRECTORY_SEPARATOR . "../data/lexicon_positif.json");
        $list_positif = json_decode($json_lexicon_positif);
        $json_lexicon_negatif = file_get_contents( dirname(__FILE__) . DIRECTORY_SEPARATOR . "../data/lexicon_negatif.json");
        $list_negatif = json_decode($json_lexicon_negatif);
        $json_lexicon_negasi = file_get_contents( dirname(__FILE__) . DIRECTORY_SEPARATOR . "../data/lexicon_negasi.json");
        $list_negasi = json_decode($json_lexicon_negasi);

        $this->lexicon_positif = $list_positif[2]->data;
        $this->lexicon_negatif = $list_negatif[2]->data;
        $this->lexicon_negasi = $list_negasi[2]->data;

        $this->kalimat = $kalimat;
        $this->_process();
    }

    public function say()
    {
        echo "Hi there..";
    }

    public function getKamusLexicon($sentimen = null)
    {
        if($sentimen == null) {
            return array(
                'positif' => $this->lexicon_positif,
                'negatif' => $this->lexicon_negatif,
                'negasi' => $this->lexicon_negasi,
            );
        }
        else {
            switch ($sentimen) {
                case 'positif':
                    return $this->lexicon_positif;
                    break;
                
                case 'negatif' : 
                    return $this->lexicon_negasi;
                    break;

                case 'negasi' :
                    return $this->lexicon_negasi;
                    break;
                default:
                    return false;
                    break;
            }
        }
        return $this->lexicon_positif;
    }

    public function cariKata($word, $kamus)
    {
        switch ($kamus) {
            case 'positif':
                $kata_id = array_search($word, array_column($this->lexicon_positif, 'Lexicon') );
                return ($kata_id) ? $this->lexicon_positif[$kata_id] : null;
                break;
            
            case 'negatif':
                $kata_id = array_search($word, array_column($this->lexicon_negatif, 'Lexicon') );
                return ($kata_id) ? $this->lexicon_negatif[$kata_id] : null;
                break;

            case 'negasi':
                $kata_id = array_search($word, array_column($this->lexicon_negasi, 'negasi') );
                return ($kata_id) ? $this->lexicon_negatif[$kata_id] : null;
                break;
            default:
                return false;
                break;
        }
        
    }

    private function _process()
    {
        $ngram = [];
        $lexicon = [];
        $lexicon_akhir = [];
        $lst = [];
        $ngram['unigram'] = $this->_Ngrams($this->kalimat, 1);
        $ngram['bigram'] = $this->_Ngrams($this->kalimat, 2);
        $ngram['trigram'] = $this->_Ngrams($this->kalimat, 3);

        foreach ($ngram['unigram'] as $key => $unigram) {
            if($this->_searchInDict($unigram, 'lexicon_positif', 'Lexicon')) {
                $lexicon['positif'][] = $unigram;
            }
            if($this->_searchInDict($unigram, 'lexicon_negatif', 'Lexicon')) {
                $lexicon['negatif'][] = $unigram;
            }
            if ($this->_searchInDict($unigram, 'lexicon_negasi', 'negasi')) {
                $lexicon['negasi'][] = $unigram;
            }
        }
        foreach ($ngram['bigram'] as $key => $bigram) {
            if($this->_searchInDict($bigram, 'lexicon_positif', 'Lexicon')) {
                $lexicon['positif'][] = $bigram;
            }
            if($this->_searchInDict($bigram, 'lexicon_negatif', 'Lexicon')) {
                $lexicon['negatif'][] = $bigram;
            }
            if ($this->_searchInDict($bigram, 'lexicon_negasi', 'negasi')) {
                $lexicon['negasi'][] = $bigram;
            }
        }
        foreach ($ngram['trigram'] as $key => $trigram) {
            if($this->_searchInDict($trigram, 'lexicon_positif', 'Lexicon')) {
                $lexicon['positif'][] = $trigram;
            }
            if($this->_searchInDict($trigram, 'lexicon_negatif', 'Lexicon')) {
                $lexicon['negatif'][] = $trigram;
            }
            if ($this->_searchInDict($trigram, 'lexicon_negasi', 'negasi')) {
                $lexicon['negasi'][] = $trigram;
            }
        }
        $hasilString = '';
        $lexicon_negasi = isset($lexicon['negasi']) ? count($lexicon['negasi']) : 0;
        /* cek Negasi */
        if($lexicon_negasi == 0){
            /* hitung lexicon */
            $hasilString = $this->_hitungProbabilitasSentimen($lexicon);
        }
        else {
            $hasilString = 'lanjut perhitungan ';
            $ls_positif = isset($lexicon['positif']) ? $lexicon['positif'] : [];
            $ls_negatif = isset($lexicon['negatif']) ? $lexicon['negatif'] : [];
            foreach($lexicon['negasi'] as $negasi) {
                foreach ($ls_positif as $keyPosLex => $positif) {
                    $keySearch = $negasi . " " . $positif;
                    $find = stripos($this->kalimat, $keySearch);
                    // dd($find);
                    if ($find !== false) {
                        // cek kedalam array lexicon untuk mencegah duplikasi
                        $cekInLexiconPosArr = array_search($keySearch, $ls_positif);
                        $cekInLexiconNegArr = array_search($keySearch, $ls_negatif);
                        // var_dump(0);
                        if ($cekInLexiconPosArr) {
                            /* hilangkan pasangan dari array lexicon positif (positif/negatif)*/
                            // unset($lexicon['positif'][$keyPosLex]);
                            unset($ls_positif[$keyPosLex]);
                        }
                        elseif ($cekInLexiconNegArr) {
                            /* hilangkan pasangan dari array lexicon positif (positif/negatif)*/
                            // unset($lexicon['positif'][$keyPosLex]);
                            unset($ls_positif[$keyPosLex]);
                        }
                        else {
                            /* lakukan proses negasi */
                            /* tambahkan string yang ditemukan ke array lexicon negatif (negasi dari positif) */
                            // (optional) hapus dari lexicon positif
                            // array_push($lexicon['negatif'], $keySearch);
                            unset($ls_positif[$keyPosLex]);
                            array_push($ls_negatif, $keySearch);
                        }
                    }
                    else {
                        /* hitung lexicon */
                        $hasilString = $this->_hitungProbabilitasSentimen($lexicon);

                    }
                }
                /* menyusun ulang array lexicon positif */
                // $lexicon['positif'] = array_values($lexicon['positif']);
                // $ls_positif = array_values($ls_positif);


                foreach ($ls_negatif as $keyNegLex => $negatif) {
                    $keySearch = $negasi . " " . $negatif;
                    // echo $this->kalimat;
                    $find = stripos($this->kalimat, $keySearch);
                    // var_dump($find);
                    if ($find !== false) {
                        $cekInLexiconPosArr = array_search($keySearch, $ls_positif);
                        $cekInLexiconNegArr = array_search($keySearch, $ls_negatif);

                        if ($cekInLexiconPosArr) {
                            /* hilangkan pasangan dari array lexicon negatif (positif/negatif)*/
                            // unset($lexicon['negatif'][$keyNegLex]);
                            unset($ls_negatif[$keyNegLex]);
                        }
                        elseif ($cekInLexiconNegArr) {
                            /* hilangkan pasangan dari array lexicon negatif (positif/negatif)*/
                            // unset($lexicon['negatif'][$keyNegLex]);
                            unset($ls_negatif[$keyNegLex]);
                        }
                        else {
                            /* lakukan proses negasi */
                            /* tambahkan string yang ditemukan ke array lexicon positif (negasi dari negatif) */
                            // (optional) hapus dari lexicon positif
                            // array_push($lexicon['positif'], $keySearch);
                            unset($ls_negatif[$keyNegLex]);
                            array_push($ls_positif, $keySearch);
                        }
                    }
                    else {
                        /* hitung lexicon */
                        $hasilString = $this->_hitungProbabilitasSentimen($lexicon);
                    }
                }

                /* menyusun ulang array lexicon negatif */
                // $lexicon['negatif'] = array_values($lexicon['negatif']);
                // $lst['negatif'] = array_values($ls_negatif);
            }
            /* lakukan rearrange array untuk memastikan tidak ada value array yg kosong */
            // $lexicon['positif'] = array_values($lexicon['positif']);
            // $lexicon['negatif'] = array_values($lexicon['negatif']);
            $lst['positif'] = array_values($ls_positif);
            $lst['negatif'] = array_values($ls_negatif);
            $lexicon_akhir['positif'] = $lst['positif'];
            $lexicon_akhir['negatif'] = $lst['negatif'];
            $lexicon_akhir['negasi'] = $lexicon['negasi'];
            // dd($lexicon_akhir);
            $hasilString = $this->_hitungProbabilitasSentimen($lexicon_akhir);
        }

        // dd($lexicon);

        $this->hasil = $hasilString; 
        $this->dump = array(
            'ngram' => $ngram,
            'lexicon' => $lexicon,
            'lexiconAfterNegasi' => $lexicon_akhir,
            'sentimen' => $hasilString,
            'catatan' => 'LexiconAfterNegasi tidak bisa ditampilkan'
        );

        // return array(
        //     'ngram' => $ngram,
        //     'lexicon' => $lexicon,
        //     'lexiconAfterNegasi' => $lexicon_akhir,
        //     'sentimen' => $hasilString
        // );

        // return $hasilString; 
    }

    public function getDetail()
    {
        
        return $this->dump;
    }

    public function __get($var)
    {
        return $this->$var;
    }

    /**
     * 
     * untuk mencari kata pada kamus lexicon
     *
     * @param String $word Kata yang dicari
     * @param String $kamus kamus yang digunakan
     * @param String $key field (key array) kamus
     * @return Bool 
     **/
    private function _searchInDict($word, $kamus, $key)
    {
        switch ($kamus) {
            case 'lexicon_positif':
                return array_search($word, array_column($this->lexicon_positif, $key) );
                break;
            
            case 'lexicon_negatif':
                return array_search($word, array_column($this->lexicon_negatif, $key) );
                break;

            case 'lexicon_negasi':
                return array_search($word, array_column($this->lexicon_negasi, $key) );
                break;
            default:
                return false;
                break;
        }
        
    }

    private function _Ngrams($doc, $n){
        $ls_word = explode(" ", $doc);
        $n_word = count($ls_word);
        $ls_ngrams = [];
        for ($i=0; $i+($n-1) < $n_word; $i++) { 

            $ngrams = "";
            // looping sebanyak n
            for ($j=$i; $j < $n+$i; $j++) { 
                $ngrams = $ngrams ." ". $ls_word[$j];
            }

            $ls_ngrams[] = trim($ngrams, " ");
        }

        return $ls_ngrams;
    }

    private function _hitungProbabilitasSentimen($lexicon)
    {
        /* hitung lexicon */
        $lexicon_positif = isset($lexicon['positif']) ? count($lexicon['positif']) : 0;
        $lexicon_negatif = isset($lexicon['negatif']) ? count($lexicon['negatif']) : 0;
        $hasilString = '';
        if ( $lexicon_positif == $lexicon_negatif ) {
            $hasilString = 'netral';
        }
        elseif ($lexicon_positif > $lexicon_negatif) {
            $hasilString = 'positif';
        }
        elseif ($lexicon_positif < $lexicon_negatif) {
            $hasilString = 'negatif';
        }

        return $hasilString;
        
    }
}

?>