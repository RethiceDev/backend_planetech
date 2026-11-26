<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name')->get();

        return view('admin.employees', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $this->validateEmployee($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('employees', 'public');
        }

        Employee::create($data);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employé enregistré avec succès.');
    }

    public function edit(Employee $employee)
    {
        $employees = Employee::orderBy('name')->get();

        return view('admin.employees', compact('employee', 'employees'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateEmployee($request, $employee);

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }

            $data['photo'] = $request->file('photo')->store('employees', 'public');
        }

        $employee->update($data);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employé modifié avec succès.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employé supprimé avec succès.');
    }

    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $macRegex = '/^([0-9A-Fa-f]{2}[:\-]){5}[0-9A-Fa-f]{2}$/';

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'prenoms'       => ['required', 'string', 'max:150'],
            'email'         => ['required', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employee?->id)],
            'telephone'     => ['required', 'digits:10'],
            'service'       => ['required', 'string', 'max:100'],
            'poste'         => ['required', 'string', 'max:100'],
            'date_embauche' => ['required', 'date'],
            'mac_address'   => ['required', 'regex:' . $macRegex, Rule::unique('employees', 'mac_address')->ignore($employee?->id)],
            'mac_address_2' => ['nullable', 'regex:' . $macRegex, Rule::unique('employees', 'mac_address_2')->ignore($employee?->id)],
            'type_contrat'  => ['nullable', Rule::in(['CDI', 'CDD', 'Stage', 'Prestataire'])],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['mac_address'] = strtoupper(str_replace('-', ':', $data['mac_address']));

        if (!empty($data['mac_address_2'])) {
            $data['mac_address_2'] = strtoupper(str_replace('-', ':', $data['mac_address_2']));
        }

        return $data;
    }
}