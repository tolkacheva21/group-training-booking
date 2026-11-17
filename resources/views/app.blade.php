<x-app-layout>
    <style>
        * {
            box-sizing: border-box;
        }




        .page {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            padding: 25px;
            background: white;
            border: 1px solid #dce3dd;
            border-radius: 6px;
        }

        .top-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 20px;
            font-size: 26px;
        }

        .top-line h1 {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #dce3dd;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #edf3ee;
        }

        label {
            display: block;
            margin: 15px 0 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #b8c5bc;
            border-radius: 4px;
            font: inherit;
        }

        input:focus {
            outline: 2px solid #365d46;
            outline-offset: 1px;
        }

        .button, button {
            display: inline-block;
            margin: 3px 0;
            padding: 8px 12px;
            border: 0;
            border-radius: 4px;
            background: #365d46;
            color: white;
            font: inherit;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .button-edit {
            background: #946a26;
        }

        .button-danger {
            background: #ad4444;
        }

        .inline {
            display: inline;
        }

        .errors {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 4px solid #ad4444;
            background: #fbeeee;
            color: #842d2d;
        }

        .errors ul {
            margin-bottom: 0;
        }


        .details {
            display: grid;
            grid-template-columns: 220px 1fr;
            margin: 0 0 25px;
            border: 1px solid #dce3dd;
            border-radius: 6px;
            overflow: hidden;
        }

        .details dt,
        .details dd {
            margin: 0;
            padding: 12px 15px;
            border-bottom: 1px solid #dce3dd;
        }

        .details dt {
            background: #edf3ee;
            font-weight: bold;
        }

        .details dd {
            background: white;
        }

        .details dt:last-of-type,
        .details dd:last-of-type {
            border-bottom: 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .actions .button {
            margin: 0;
        }
    </style>

    <div class="page">
        @if ($errors->any())
            <div class="errors">
                <p>Исправьте ошибки:</p>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</x-app-layout>
