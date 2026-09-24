<?php

namespace App\Http\Controllers;

use App\Traits\ConferenceTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\HallModels\HallAttribute;
use App\HallModels\HallRoomFacility;


class ConferenceHallMasterController extends Controller
{
    use ConferenceTraits;
    
    /* *********************************************************************************************************************
    * @author : Sadyasnata Patasani
    * @date : 10/06/2026
    * Description : This function displays the Hall Attribute page and loads all active hall attributes from the m_attribute table.
    * @param Request @request - The current HTTP request instance.
    ************************************************************************************************************************ */
    
    public function hallAttributeTermAddRequest(Request $request)
    {
        $HallAttributes = DB::table('m_attribute as a')
            ->where('a.is_deleted', 0)
            ->whereRaw("
                a.id = (
                    SELECT MAX(a2.id)
                    FROM m_attribute AS a2
                    WHERE a2.is_deleted = 0
                    AND LOWER(TRIM(a2.attribute_name)) =
                        LOWER(TRIM(a.attribute_name))
                )
            ")
            ->orderBy('a.id', 'desc')
            ->get();

        foreach ($HallAttributes as $value) {
            $value->encrypted_id = $this->encryptData($value->id);
        }

        return view('hall.hall-attribute', compact('HallAttributes'));
    }

 
     /* *********************************************************************************************************************
    * @author : Sadyasnata Patasani
    * @date : 10/06/2026
    * Description : This function validates and stores a new hall attribute in the m_attribute table.
    * @param Request @request - The submitted hall attribute form data.
    ************************************************************************************************************************ */

    public function hallAttributeAddRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validate->fails()) {
            return Redirect::to('hall-attribute')
                ->withErrors($validate)
                ->withInput();
        }

        $attributeName = trim($request->name);

        $duplicateExists = DB::table('m_attribute')
            ->where('is_deleted', 0)
            ->whereRaw('LOWER(TRIM(attribute_name)) = ?', [
                strtolower($attributeName)
            ])
            ->exists();

        if ($duplicateExists) {
            return Redirect::to('hall-attribute')
                ->withInput()
                ->with('error', 'Attribute already exists.');
        }

        DB::table('m_attribute')->insert([
            'attribute_name' => $attributeName,
            'status'         => '1',
            'created_by'     => Auth::user()->id,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_by'     => Auth::user()->id,
            'updated_at'     => date('Y-m-d H:i:s'),
            'is_deleted'     => 0,
        ]);

        Session::flash('success', 'Attribute added successfully.');

        return Redirect::to('hall-attribute');
    }


     /* *********************************************************************************************************************
    * @author : Sadyasnata Patasani
    * @date : 10/06/2026
    * Description : This function decrypts the hall attribute ID, loads the selected attribute, and displays its related hall facility terms.
    * @param string|null $encryptedId - The encrypted hall attribute ID from the URL.
    ************************************************************************************************************************ */

    public function hallAttributeTerm($encryptedId = null)
    {
        if (empty($encryptedId)) {
            return redirect('hall-attribute');
        }

        try {
            $id = $this->decryptData($encryptedId);
        } catch (\Exception $e) {
            return redirect('hall-attribute');
        }

        $ServiceAttribute = DB::table('m_attribute')
            ->where('id', $id)
            ->where('is_deleted', 0)
            ->first();

        if (empty($ServiceAttribute)) {
            return redirect('hall-attribute');
        }

        $AttributeTerms = HallRoomFacility::where('attribute_id', $id)
            ->where('facility_type', 'HALL')
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();

        return view('hall.hall-attribute-terms', compact('ServiceAttribute', 'AttributeTerms'));
    }

     /* *********************************************************************************************************************
    * @author : Sadyasnata Patasani
    * @date : 10/06/2026
    * Description : This function validates and stores a new facility term under the selected hall attribute in the m_hfacilities table.
    * @param Request @request - The submitted hall attribute term form data.
    ************************************************************************************************************************ */

    public function hallAttributeTermSaveRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'attr_id' => 'required',
            'name' => 'required|string|max:100',
        ]);

        $encryptedId = $this->encryptData($request->attr_id);

        if ($validate->fails()) {
            return Redirect::to('hall-attribute-terms/' . $encryptedId)
                ->withErrors($validate)
                ->withInput();
        }

        HallRoomFacility::insert([
            'attribute_id' => $request->attr_id,
            'facility_type' => 'HALL',
            'facility_name' => $request->name,
            'status' => '1',
            'created_by' => Auth::user()->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::user()->id,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_deleted' => 0,
        ]);

        Session::flash('success', 'Attribute term added successfully.');
        return Redirect::to('hall-attribute-terms/' . $encryptedId);
    }

     /* *********************************************************************************************************************
    * @author : Sadyasnata Patasani
    * @date : 10/06/2026
    * Description : This function handles AJAX operations for hall attributes and terms, including edit and delete actions.
    * @param Request @request - The AJAX request data containing operation type and selected record details.
    ************************************************************************************************************************ */

   public function hallOprsn(Request $request)
   {
        if ($request->request_type == 'delete-hall-attribute') {
            $ids = json_decode($request->IdArray, true);

            if (empty($ids)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No items selected.'
                ]);
            }

            DB::table('m_attribute')
                ->whereIn('id', $ids)
                ->update([
                    'is_deleted' => 1,
                    'updated_by' => Auth::user()->id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'status' => 1,
                'message' => 'Attribute deleted successfully.'
            ]);
        }

        if ($request->request_type == 'save-hall-attribute') {
            if (empty($request->Id) || empty($request->attrName)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Please fill all data.'
                ]);
            }

            DB::table('m_attribute')
                ->where('id', $request->Id)
                ->update([
                    'attribute_name' => $request->attrName,
                    'updated_by' => Auth::user()->id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'status' => 1,
                'message' => 'Attribute updated successfully.'
            ]);
        }

        if ($request->request_type == 'delete-hall-attribute-term') {
            $ids = json_decode($request->IdArray, true);

            if (empty($ids)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No items selected.'
                ]);
            }

            HallRoomFacility::whereIn('id', $ids)
                ->where('facility_type', 'HALL')
                ->update([
                    'is_deleted' => 1,
                    'updated_by' => Auth::user()->id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'status' => 1,
                'message' => 'Attribute term deleted successfully.'
            ]);
        }

        if ($request->request_type == 'save-hall-terms-changes') {
            if (empty($request->id) || empty($request->name)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Please fill all data.'
                ]);
            }

            HallRoomFacility::where('id', $request->id)
                ->where('facility_type', 'HALL')
                ->update([
                    'facility_name' => $request->name,
                    'updated_by' => Auth::user()->id,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'status' => 1,
                'message' => 'Attribute term updated successfully.'
            ]);
        }

        return response()->json([
            'status' => 0,
            'message' => 'Invalid request.'
        ]);
    }

}
