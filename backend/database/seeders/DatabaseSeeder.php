<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\Post;
use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DatabaseSeeder cannot run in production.');
        }

        $teacher = User::firstOrCreate(['email' => 'teacher@example.com'], ['name' => 'Professor Teste', 'birthdate' => '1990-01-01', 'system_role' => 'teacher', 'password' => Str::random(48)]);
        $representative = User::firstOrCreate(['email' => 'representative@example.com'], ['name' => 'Representante Teste', 'birthdate' => '2000-01-01', 'system_role' => 'representative', 'password' => Str::random(48)]);
        $student = User::firstOrCreate(['email' => 'student@example.com'], ['name' => 'Aluno Teste', 'birthdate' => '2005-01-01', 'system_role' => 'student', 'password' => Str::random(48)]);
        User::firstOrCreate(['email' => 'coordinator@example.com'], ['name' => 'Coordenador Teste', 'birthdate' => '1985-01-01', 'system_role' => 'coordinator', 'password' => Str::random(48)]);
        User::firstOrCreate(['email' => 'admin@example.com'], ['name' => 'Administrador Teste', 'birthdate' => '1980-01-01', 'system_role' => 'admin', 'password' => Str::random(48)]);

        $class1 = SchoolClass::firstOrCreate(['name' => '3 ano A', 'course' => 'Ensino Medio', 'year' => 2026], ['semester' => 2, 'starts_on' => '2026-02-01', 'ends_on' => '2026-12-15']);
        $class2 = SchoolClass::firstOrCreate(['name' => '2 ano B', 'course' => 'Tecnico em Informatica', 'year' => 2026], ['semester' => 4, 'starts_on' => '2026-02-01', 'ends_on' => '2026-12-15']);
        $class1->members()->syncWithoutDetaching([$teacher->id => ['role' => 'teacher'], $representative->id => ['role' => 'representative'], $student->id => ['role' => 'student']]);
        $class2->members()->syncWithoutDetaching([$teacher->id => ['role' => 'teacher']]);

        $task1 = Task::firstOrCreate(['class_id' => $class1->id, 'title' => 'Lista de Exercicios de Matematica'], ['description' => 'Resolver os exercicios do capitulo 5.', 'priority' => 'high', 'due_date' => now()->addDays(7), 'created_by' => $teacher->id]);
        Task::firstOrCreate(['class_id' => $class1->id, 'title' => 'Pesquisa sobre Python'], ['description' => 'Apresentar uma pesquisa sobre Python.', 'priority' => 'medium', 'due_date' => now()->addDays(7), 'created_by' => $teacher->id]);
        TaskCompletion::firstOrCreate(['user_id' => $student->id, 'task_id' => $task1->id], ['completed_at' => now()]);
        Announcement::firstOrCreate(['class_id' => $class1->id, 'title' => 'Prova de Matematica'], ['content' => 'A prova sera na proxima sexta-feira.', 'created_by' => $teacher->id]);

        $organization = Organization::firstOrCreate(['name' => 'Gremio Estudantil'], ['description' => 'Organizacao estudantil da escola.', 'type' => 'institutional']);
        $organization->members()->syncWithoutDetaching([$teacher->id => ['role' => 'teacher'], $student->id => ['role' => 'member'], $representative->id => ['role' => 'representative']]);
        Post::firstOrCreate(['organization_id' => $organization->id, 'title' => 'Eleicao do Gremio'], ['description' => 'As eleicoes acontecem na proxima semana.', 'created_by' => $teacher->id]);
    }
}
