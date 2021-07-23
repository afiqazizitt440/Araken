<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Distributor_type;
use App\Models\Fund_summary;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Echo_;
use Ixudra\Curl\Facades\Curl;
use PhpParser\Node\Stmt\Foreach_;
use Psy\Command\WhereamiCommand;

    //TEST DATA
    //1 .Maybank Asset : 2038 
    //2 .Amfund : 1042 working
    //3 .Phillip : 1056 working
    //4 .Public : 2032 
    //5 .AIA : 3000   
    //6. OCBC : 2037 
    //8. BLUEPRINT : 2091

class AMSFCalculationController extends Controller
{
    private $MemberId;
    private $Year;
    
    //calculation
    //public function show($MemberId, $Year)
    public function AMSFCalc(Request $request)  
    {   
        // $MemberId = '2038'; //test data
        // $Year = '2020'; //test data
        
        $FMemberID = '2038';
        $Year = '2020';
        
        //Member type combination for amsf method
        // $typeCase = [
        //     [""],["1,2"],["1,4"],["1,5"],["1,2,4"],["1,2,5"],["1,4,5"],["1,2,4,5"],["2,5"],
        //     ["3,6"],["4,5"],["1"],["2"],["3"],["4"],["5"],["6"]
        // ]; 
        $typeCase = array 
            ([],[1,2],[1,4],[1,5],[1,2,4],[1,2,5],[1,4,5],[1,2,4,5],[2,5],
            [3,6],[4,5],[1],[2],[3],[4],[5],[6]);
        
        //total count for member types
        $NType = array(0,2,2,2,3,3,3,4,2,2,2,1,1,1,1,1,1);

        //count member type - eloqent
        $NMemberShip = DB::table('DISTRIBUTOR_TYPE')
        ->select(DB::raw('count(*) as BIL'))
        ->where('DISTRIBUTOR_ID', $request->MEMBER_CODE)->get();
        //->where('DISTRIBUTOR_ID', 2038)->get();
        
        foreach($NMemberShip as $NMemberShips)
            {
                $NMemberShip = $NMemberShips->BIL;
            }
        
        //test ntype
        // for($i = 1; $i <= 16; $i++)
        // {print $NType[$i];}

        // //test ntype
        // for($i = 1; $i <= 16; $i++)
        // {print $typeCase[$i];}
        // die();

        for($i = 1; $i <= 16; $i++)
        {
            
            //count member type
            $nFilterRec = DB::table('DISTRIBUTOR_TYPE')
            ->select(DB::raw('count(*) as BIL'))
            ->where('DISTRIBUTOR_ID', $request->MEMBER_CODE)
            ->whereIn('DISTRIBUTOR_TYPE', $typeCase[$i])->get();
            //->where('DISTRIBUTOR_ID', $request->MEMBER_CODE) CHANGE THIS
            //->where('DISTRIBUTOR_ID',2038)

            foreach($nFilterRec as $nFilterRecs)
            {
                $nFilterRec = $nFilterRecs->BIL;
                //echo '$nFilterRec '.$nFilterRec.'<br>';
            }
            
            //                   3                           3
            if($NType[$i] == $nFilterRec && $NType[$i] == $NMemberShip) 
            {   
                //method 1
                if($i == 1 OR $i == 11)
                {
                    //method 1
                    echo 'method 1, ';
                    return $this->method1($FMemberID, $Year);
                }              
                if($i == 2 OR $i == 4 OR $i == 6 OR $i == 7)
                {
                    //method 2
                    echo 'method 2<br>';
                    return $this->method2($FMemberID, $Year);
                }
                if($i == 3 OR $i == 5)
                {
                    //method 3 Done
                    echo 'method 3<br>';
                    return $this->method3($FMemberID, $Year);
                }
                if($typeCase == 8)
                {
                    //method 4
                    echo 'method 4<br>';
                    return $this->method4($FMemberID, $Year);
                }
                if($typeCase == 10 OR $typeCase == 14)
                {
                    //method 5
                    return $this->method5($FMemberID, $Year);
                }
                if($typeCase == 12)
                {
                    //method 6
                    return $this->method6($FMemberID, $Year);
                }           
                if($typeCase == 15)
                {
                    //method 7
                    return $this->method7($FMemberID, $Year);
                }
                if($typeCase == 9)
                {
                    //method 8
                    return $this->method8($FMemberID, $Year);
                }
                if($typeCase == 13)
                {
                    //method 9
                    return $this->method9($FMemberID, $Year);
                }
                if($typeCase == 16)
                {
                    //method 10
                    return $this->method10($FMemberID, $Year);
                }                 
            }
        }
        

    }

    public function method1($FMemberID, $Year)
    {
        $record = DB::table('FUND_SUMMARY')
        ->select('TOTAL_UTC_LEVY','TOTAL_UTC_CARD_FEES','TOTAL_PRC_LEVY','TOTAL_PRC_CARD_FEES','TOTAL_UTC_PRC_LEVY','TOTAL_UTC_PRC_CARD_FEES',
        'TOTAL_SPLIT_UTC_LEVY','TOTAL_SPLIT_UTC_CARD_FEES','TOTAL_SPLIT_PRC_LEVY','TOTAL_SPLIT_PRC_CARD_FEES','TOTAL_WAIVER_LEVY','AUM_GROUP_A AS AUMA',
        'AUM_GROUP_B AS AUMB','NORMAL_LOAD_SALES AS LoadNormal','LOW_LOAD_SALES AS LoadLow','NO_LOAD_SALES AS LoadNo','AUM_GROUP_A_UTC AS totalA',
        'AUM_GROUP_B_UTC AS totalB')
        ->where('DISTRIBUTOR_ID', $FMemberID)
        ->where('AMSF_YEAR', $Year)->get();

        foreach($record as $item)
        {   
            //Consultant and Card 
            $UTCLevy = $item->TOTAL_UTC_LEVY; //total utc levy = total consultant * utc renewal fee
            $UTCCardFee = $item->TOTAL_UTC_CARD_FEES; //total utc card fees = total consultant * utc card fee

            $PRCLevy = $item->TOTAL_PRC_LEVY; //total prc levy = total consultant * prc renewal fee
            $PRCCardFee = $item->TOTAL_PRC_CARD_FEES; //total prc card fees = total consultant * prc card fee

            $BothLevy = $item->TOTAL_UTC_PRC_LEVY; //total prc levy = total consultant * prc renewal fee
            $BothCardFee = $item->TOTAL_UTC_PRC_CARD_FEES; //total prc card fees = total consultant * prc card fee

            $SplitUTCLevy = $item->TOTAL_SPLIT_UTC_LEVY; //total prc split levy = total prc split consultant * prc split renewal fee
            $SplitUTCCardFee = $item->TOTAL_SPLIT_UTC_CARD_FEES; //total utc split card fees = total utc split consultant * utc split card fee 

            $SplitPRCLevy = $item->TOTAL_SPLIT_PRC_LEVY; //total prc split levy = total prc split consultant * prc split renewal fee
            $SplitPRCCardFee = $item->TOTAL_SPLIT_PRC_CARD_FEES; //total prc split card fees = total prc split consultant * prc split card fee 
            
            $WaiverLevy = $item->TOTAL_WAIVER_LEVY; //total prc waiver levy = total consultant * waiver renewal fee
            

            //AUM ALL
            $AUMA = $item->AUMA; //Total AUM Fund Group A
            $amsfa = $this->calamsf($AUMA,'LGroupA'); //calculation for total AUM Fund Group A
            $AUMB = $item->AUMB; //Total AUM Fund Group B  
            $amsfb = $this->calamsf($AUMB,'LGroupB'); //calculation for total AUM Fund Group B
            
            //AUM all
            $amsfAB = $amsfa + $amsfb;
            $amsfAB=($amsfAB<10000 ? 10000 : $amsfAB);

            //AUM ALL
            $AUMAUTMC = $item->totalA; //Total AUM Fund Group A
            $amsfaUTMC = $this->calamsf($AUMAUTMC,'LGroupA'); //calculation for total AUM Fund Group A
            $AUMBUTMC = $item->totalB; //Total AUM Fund Group B  
            $amsfbUTMC = $this->calamsf($AUMBUTMC,'LGroupB'); //calculation for total AUM Fund Group B

            //AUM UTMC only
            $amsfABUTMC = $amsfaUTMC + $amsfbUTMC;
            $amsfABUTMC=($amsfABUTMC<10000 ? 10000 : $amsfABUTMC);

            //total AMSF
            $TotalAMSF = $UTCLevy+$UTCCardFee+$PRCLevy+$PRCCardFee+$BothLevy+$BothCardFee+$SplitUTCLevy
            +$SplitUTCCardFee+$SplitPRCLevy+$SplitPRCCardFee+$amsfAB-$WaiverLevy;

            $TotalAMSF = str_replace(',','',$TotalAMSF); 

            echo 'UTCLevy: '.$UTCLevy.'<br>';
            echo 'UTCCardFee: '.$UTCCardFee.'<br>';
            echo 'PRCLevy: '.$PRCLevy.'<br>';
            echo 'PRCCardFee: '.$PRCCardFee.'<br>';
            echo 'BothLevy: '.$BothLevy.'<br>';
            echo 'BothCardFee: '.$BothCardFee.'<br>';   
            echo 'SplitUTCLevy: '.$SplitUTCLevy.'<br>';
            echo 'SplitUTCCardFee: '.$SplitUTCCardFee.'<br>';
            echo 'SplitPRCLevy: '.$SplitPRCLevy.'<br>';
            echo 'SplitPRCCardFee: '.$SplitPRCCardFee.'<br>';
            echo 'WaiverLevy: '.$WaiverLevy.'<br><br>';

            echo 'LEVY_GROUP_A: '.$amsfa.'<br>';
            echo 'LEVY_GROUP_A_UTC: '.$amsfaUTMC.'<br>';
            echo 'LEVY_GROUP_B: '.$amsfb.'<br>';
            echo 'LEVY_GROUP_B_UTC: '.$amsfbUTMC.'<br><br>';

            echo 'LEVY_PAYABLE: '.$amsfAB.'<br>';
            echo 'LEVY_PAYABLE_UTC: '.$amsfABUTMC.'<br>';
            echo 'TOTAL AMSF: '.$TotalAMSF.'<br>';
            echo 'METHOD: 1<br><br>';

            die();
            //update database
            $case = DB::find($FMemberID);
           

        }
    }

    public function method2($FMemberID, $Year)
    {
        echo 'Member ID : '.$FMemberID.'<br>';
        echo '$Year : '.$Year.'<br><br>';
        echo 'm2';

        $record = DB::table('FUND_SUMMARY')
        ->select('TOTAL_UTC_LEVY','TOTAL_UTC_CARD_FEES','TOTAL_PRC_LEVY','TOTAL_PRC_CARD_FEES','TOTAL_UTC_PRC_LEVY','TOTAL_UTC_PRC_CARD_FEES',
        'TOTAL_SPLIT_UTC_LEVY','TOTAL_SPLIT_UTC_CARD_FEES','TOTAL_SPLIT_PRC_LEVY','TOTAL_SPLIT_PRC_CARD_FEES','TOTAL_WAIVER_LEVY','AUM_GROUP_A AS AUMA',
        'AUM_GROUP_B AS AUMB','NORMAL_LOAD_SALES AS LoadNormal','LOW_LOAD_SALES AS LoadLow','NO_LOAD_SALES AS LoadNo','AUM_GROUP_A_UTC AS totalA',
        'AUM_GROUP_B_UTC AS totalB')
        ->where('DISTRIBUTOR_ID', $FMemberID)
        ->where('AMSF_YEAR', $Year)->get();

        foreach($record as $item)
        {   
            //Consultant and Card 
            $UTCLevy = $item->TOTAL_UTC_LEVY; //total utc levy = total consultant * utc renewal fee
            $UTCCardFee = $item->TOTAL_UTC_CARD_FEES; //total utc card fees = total consultant * utc card fee

            $PRCLevy = $item->TOTAL_PRC_LEVY; //total prc levy = total consultant * prc renewal fee
            $PRCCardFee = $item->TOTAL_PRC_CARD_FEES; //total prc card fees = total consultant * prc card fee

            $BothLevy = $item->TOTAL_UTC_PRC_LEVY; //total prc levy = total consultant * prc renewal fee
            $BothCardFee = $item->TOTAL_UTC_PRC_CARD_FEES; //total prc card fees = total consultant * prc card fee

            $SplitUTCLevy = $item->TOTAL_SPLIT_UTC_LEVY; //total prc split levy = total prc split consultant * prc split renewal fee
            $SplitUTCCardFee = $item->TOTAL_SPLIT_UTC_CARD_FEES; //total utc split card fees = total utc split consultant * utc split card fee 

            $SplitPRCLevy = $item->TOTAL_SPLIT_PRC_LEVY; //total prc split levy = total prc split consultant * prc split renewal fee
            $SplitPRCCardFee = $item->TOTAL_SPLIT_PRC_CARD_FEES; //total prc split card fees = total prc split consultant * prc split card fee 
            
            $WaiverLevy = $item->TOTAL_WAIVER_LEVY; //total prc waiver levy = total consultant * waiver renewal fee
            

            //AUM ALL
            $AUMA = $item->AUMA; //Total AUM Fund Group A
            $amsfa = $this->calamsf($AUMA,'LGroupA'); //calculation for total AUM Fund Group A
            $AUMB = $item->AUMB; //Total AUM Fund Group B  
            $amsfb = $this->calamsf($AUMB,'LGroupB'); //calculation for total AUM Fund Group B
            
            //AUM all
            $amsfAB = $amsfa + $amsfb;
            $amsfAB=($amsfAB<10000 ? 10000 : $amsfAB);

            //AUM ALL
            $AUMAUTMC = $item->totalA; //Total AUM Fund Group A
            $amsfaUTMC = $this->calamsf($AUMAUTMC,'LGroupA'); //calculation for total AUM Fund Group A
            $AUMBUTMC = $item->totalB; //Total AUM Fund Group B  
            $amsfbUTMC = $this->calamsf($AUMBUTMC,'LGroupB'); //calculation for total AUM Fund Group B

            //AUM UTMC only
            $amsfABUTMC = $amsfaUTMC + $amsfbUTMC;
            $amsfABUTMC=($amsfABUTMC<10000 ? 10000 : $amsfABUTMC);

            //total AMSF
            $TotalAMSF = $UTCLevy+$UTCCardFee+$PRCLevy+$PRCCardFee+$BothLevy+$BothCardFee+$SplitUTCLevy
            +$SplitUTCCardFee+$SplitPRCLevy+$SplitPRCCardFee+$amsfAB-$WaiverLevy;

            $TotalAMSF = str_replace(',','',$TotalAMSF); 

            echo 'UTCLevy: '.$UTCLevy.'<br>';
            echo 'UTCCardFee: '.$UTCCardFee.'<br>';
            echo 'PRCLevy: '.$PRCLevy.'<br>';
            echo 'PRCCardFee: '.$PRCCardFee.'<br>';
            echo 'BothLevy: '.$BothLevy.'<br>';
            echo 'BothCardFee: '.$BothCardFee.'<br>';   
            echo 'SplitUTCLevy: '.$SplitUTCLevy.'<br>';
            echo 'SplitUTCCardFee: '.$SplitUTCCardFee.'<br>';
            echo 'SplitPRCLevy: '.$SplitPRCLevy.'<br>';
            echo 'SplitPRCCardFee: '.$SplitPRCCardFee.'<br>';
            echo 'WaiverLevy: '.$WaiverLevy.'<br><br>';

            echo 'LEVY_GROUP_A: '.$amsfa.'<br>';
            echo 'LEVY_GROUP_A_UTC: '.$amsfaUTMC.'<br>';
            echo 'LEVY_GROUP_B: '.$amsfb.'<br>';
            echo 'LEVY_GROUP_B_UTC: '.$amsfbUTMC.'<br><br>';

            echo 'LEVY_PAYABLE: '.$amsfAB.'<br>';
            echo 'LEVY_PAYABLE_UTC: '.$amsfABUTMC.'<br>';
            echo 'TOTAL AMSF: '.$TotalAMSF.'<br>';
            echo 'METHOD: 1<br><br>';



            die();
            //update database
            $case = DB::find($FMemberID);
           

        }
    }

    public function method3($FMemberID, $Year)
    {
        echo 'Member ID : '.$FMemberID.'<br>';
        echo '$Year : '.$Year.'<br><br>';

        $record = DB::table('FUND_SUMMARY')
        ->select('TOTAL_UTC_LEVY','TOTAL_UTC_CARD_FEES','TOTAL_PRC_LEVY','TOTAL_PRC_CARD_FEES','TOTAL_UTC_PRC_LEVY','TOTAL_UTC_PRC_CARD_FEES',
        'TOTAL_SPLIT_UTC_LEVY','TOTAL_SPLIT_UTC_CARD_FEES','TOTAL_SPLIT_PRC_LEVY','TOTAL_SPLIT_PRC_CARD_FEES','TOTAL_WAIVER_LEVY','AUM_GROUP_A AS AUMA',
        'AUM_GROUP_B AS AUMB','NORMAL_LOAD_SALES AS LoadNormal','LOW_LOAD_SALES AS LoadLow','NO_LOAD_SALES AS LoadNo','AUM_GROUP_A_UTC AS totalA',
        'AUM_GROUP_B_UTC AS totalB')
        ->where('DISTRIBUTOR_ID', $FMemberID)
        ->where('AMSF_YEAR', $Year)->get();

        foreach($record as $item)
        {   
            //Consultant and Card 
            $UTCLevy = $item->TOTAL_UTC_LEVY; //total utc levy = total consultant * utc renewal fee
            $UTCCardFee = $item->TOTAL_UTC_CARD_FEES; //total utc card fees = total consultant * utc card fee

            $PRCLevy = $item->TOTAL_PRC_LEVY; //total prc levy = total consultant * prc renewal fee
            $PRCCardFee = $item->TOTAL_PRC_CARD_FEES; //total prc card fees = total consultant * prc card fee

            $BothLevy = $item->TOTAL_UTC_PRC_LEVY; //total prc levy = total consultant * prc renewal fee
            $BothCardFee = $item->TOTAL_UTC_PRC_CARD_FEES; //total prc card fees = total consultant * prc card fee

            $SplitUTCLevy = $item->TOTAL_SPLIT_UTC_LEVY; //total prc split levy = total prc split consultant * prc split renewal fee
            $SplitUTCCardFee = $item->TOTAL_SPLIT_UTC_CARD_FEES; //total utc split card fees = total utc split consultant * utc split card fee 

            $SplitPRCLevy = $item->TOTAL_SPLIT_PRC_LEVY; //total prc split levy = total prc split consultant * prc split renewal fee
            $SplitPRCCardFee = $item->TOTAL_SPLIT_PRC_CARD_FEES; //total prc split card fees = total prc split consultant * prc split card fee 
            
            $WaiverLevy = $item->TOTAL_WAIVER_LEVY; //total prc waiver levy = total consultant * waiver renewal fee

            //AUM ALL
            $AUMA = $item->AUMA; //Total AUM Fund Group A
            $amsfa = $this->calamsf($AUMA,'LGroupA'); //calculation for total AUM Fund Group A
            $AUMB = $item->AUMB; //Total AUM Fund Group B  
            $amsfb = $this->calamsf($AUMB,'LGroupB'); //calculation for total AUM Fund Group B
            
            //AUM all
            $amsfAB = $amsfa + $amsfb;
            $amsfAB=($amsfAB<10000 ? 10000 : $amsfAB);

            //AUM ALL
            $AUMAUTMC = $item->totalA; //Total AUM Fund Group A
            $amsfaUTMC = $this->calamsf($AUMAUTMC,'LGroupA'); //calculation for total AUM Fund Group A
            $AUMBUTMC = $item->totalB; //Total AUM Fund Group B  
            $amsfbUTMC = $this->calamsf($AUMBUTMC,'LGroupB'); //calculation for total AUM Fund Group B

            //AUM UTMC only
            $amsfABUTMC = $amsfaUTMC + $amsfbUTMC;
            $amsfABUTMC=($amsfABUTMC<10000 ? 10000 : $amsfABUTMC);

            //SALES
            $LoadNormal = $item->LoadNormal;
            $LoadLow = $item->LoadLow;
            $LoadNo = $item->LoadNo;

            //Levy factor calculation
            $LoadLow1 = $LoadLow * 0.25;
            $LoadNo1 = $LoadNo * 0;
            $sTotalLoad = $LoadNormal + $LoadLow1 + $LoadNo1;

            //Sales Factor Calculation **
            $sTotalFactor = $this->calSalesFactor($sTotalLoad);

            //total AMSF
            $TotalAMSF = $UTCLevy+$UTCCardFee+$PRCLevy+$PRCCardFee+$BothLevy+$BothCardFee+$SplitUTCLevy
            +$SplitUTCCardFee+$SplitPRCLevy+$SplitPRCCardFee+$sTotalFactor+$amsfAB-$WaiverLevy;

            $TotalAMSF = str_replace(',','',$TotalAMSF); 

            echo 'UTCLevy: '.$UTCLevy.'<br>';
            echo 'UTCCardFee: '.$UTCCardFee.'<br>';
            echo 'PRCLevy: '.$PRCLevy.'<br>';
            echo 'PRCCardFee: '.$PRCCardFee.'<br>';
            echo 'BothLevy: '.$BothLevy.'<br>';
            echo 'BothCardFee: '.$BothCardFee.'<br>';   
            echo 'SplitUTCLevy: '.$SplitUTCLevy.'<br>';
            echo 'SplitUTCCardFee: '.$SplitUTCCardFee.'<br>';
            echo 'SplitPRCLevy: '.$SplitPRCLevy.'<br>';
            echo 'SplitPRCCardFee: '.$SplitPRCCardFee.'<br>';
            echo 'WaiverLevy: '.$WaiverLevy.'<br><br>';

            echo 'LEVY_GROUP_A: '.$amsfa.'<br>';
            echo 'LEVY_GROUP_A_UTC: '.$amsfaUTMC.'<br>';
            echo 'LEVY_GROUP_B: '.$amsfb.'<br>';
            echo 'LEVY_GROUP_B_UTC: '.$amsfbUTMC.'<br><br>';

            echo 'LEVY_PAYABLE: '.$amsfAB.'<br>';
            echo 'LEVY_PAYABLE_UTC: '.$amsfABUTMC.'<br>';
            echo 'TOTAL AMSF: '.$TotalAMSF.'<br>';
            echo 'METHOD: 2<br><br>';

            //update database
            $case = DB::find($FMemberID);
           

        }
    }
    


    //function calculate Levy Factor (Company Type: UTMC, IUTA)
    public function calamsf($aum, $fieldName)
    {
        $balToCalc = $aum;
        $amsf = 0;

        //fetch rules from tb LEVY - PROB //variable in select sql**
        $Levy = DB::table('LEVY')
        ->select('LSTEP','LAUM',$fieldName)
        ->orderBy('LSTEP','ASC')->get();

        foreach($Levy as $item)
        {
            $val = $item->LAUM;
            $levy = $item->$fieldName;

           
           //calculate levy
           if($balToCalc <= $val)
           {
                $amsf += $balToCalc/1000000 * $levy; 
           }
           else
           {
                $amsf += $val/1000000 * $levy;
           }

           if($balToCalc <= $val)
           {
                break;
           }
           else
           { 
                $balToCalc -= $val;
           }
        }
        //return
        return $amsf;
    }

    //function calculate sale factor (Company Type: PRP, CUTA, CPRA, IPRA)
    public function calSalesFactor($load)
    {
        
        $sFactor = 0;


        //check sales factor in table sales factor
        $salefactor = DB::table('SALES_FACTOR')
        ->select('SALES_FEE')
        ->where('SALES_ANUAL_MIN','<=',$load)
        ->where('SALES_ANUAL_MAX','>=',$load)->get();
        //->whereBetween($load, ['SALES_ANUAL_MIN', 'SALES_ANUAL_MAX'])->get();

        //fetch sales fee
        foreach($salefactor as $item)
        {
            $sFactor = $item->SALES_FEE;
        }
       
        return $sFactor;
    } 

}
