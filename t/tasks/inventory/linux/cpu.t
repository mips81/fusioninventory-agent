#!/usr/bin/perl

use strict;
use warnings;
use lib 't/lib';

use Test::Deep;
use Test::Exception;
use Test::More;
use Test::NoWarnings;

use FusionInventory::Test::Inventory;
use FusionInventory::Agent::Task::Inventory::Linux::i386::CPU;
use FusionInventory::Agent::Task::Inventory::Linux::Alpha::CPU;
use FusionInventory::Agent::Task::Inventory::Linux::SPARC::CPU;
use FusionInventory::Agent::Task::Inventory::Linux::MIPS::CPU;
use FusionInventory::Agent::Task::Inventory::Linux::ARM::CPU;
use FusionInventory::Agent::Task::Inventory::Linux::PowerPC::CPU;

my %i386 = (
    'linux-686-1' => [
        {
            ARCH         => 'i386',
            THREAD       => 1,
            MANUFACTURER => 'Intel',
            NAME         => 'Intel(R) Pentium(R) M processor 1.73GHz',
            CORE         => 1,
            STEPPING     => '8',
            SPEED        => '1730',
            MODEL        => '13',
            FAMILYNUMBER => '6'
        }
    ],
    'rhel-5.6' => [
        {
            NAME           => 'Intel(R) Xeon(R) CPU E5620 @ 2.40GHz',
            FAMILYNUMBER   => '6',
            CORE           => '4',
            MANUFACTURER   => 'Intel',
            ARCH           => 'i386',
            MODEL          => '44',
            THREAD         => '8',
            STEPPING       => '2',
            SPEED          => '2400',
            EXTERNAL_CLOCK => 5860,
            FAMILYNAME     => 'Xeon',
            ID             => 'C2 06 02 00 FF FB EB BF',
            SERIAL         => undef
        },
        {
            NAME           => 'Intel(R) Xeon(R) CPU E5620 @ 2.40GHz',
            MANUFACTURER   => 'Intel',
            MODEL          => '44',
            SPEED          => '2400',
            THREAD         => '8',
            ARCH           => 'i386',
            CORE           => '4',
            STEPPING       => '2',
            FAMILYNUMBER   => '6',
            EXTERNAL_CLOCK => 5860,
            FAMILYNAME     => 'Xeon',
            ID             => 'C2 06 02 00 FF FB EB BF',
            SERIAL         => undef
        }
    ],
    'linux-686-samsung-nc10-1' => [
        {
            ARCH         => 'i386',
            CORE         => '1',
            SPEED        => '1600',
            THREAD       => '2',
            NAME         => 'Intel(R) Atom(TM) CPU N270 @ 1.60GHz',
            MODEL        => '28',
            MANUFACTURER => 'Intel',
            FAMILYNUMBER => '6',
            STEPPING     => '2'
        }
    ],
    'linux-2.6.35-1-core-2-thread' => [
        {
            ARCH         => 'i386',
            NAME         => 'Intel(R) Atom(TM) CPU N270 @ 1.60GHz',
            THREAD       => '2',
            SPEED        => '1600',
            STEPPING     => '2',
            CORE         => '1',
            FAMILYNUMBER => '6',
            MANUFACTURER => 'Intel',
            MODEL        => '28'
        }
    ],

# IMPORTANT : this /proc/cpuinfo is _B0RKEN_, physical_id are not correct
# please see bug: #505
    'linux-hp-dl180' => [
        {
            ARCH         => 'i386',
            FAMILYNUMBER => 6,
            SPEED        => 2000,
            STEPPING     => 5,
            MANUFACTURER => 'Intel',
            CORE         => '4',
            NAME         => 'Intel(R) Xeon(R) CPU E5504 @ 2.00GHz',
            MODEL        => 26,
            THREAD       => '1',
        }
    ],
    'toshiba-r630-2-core' => [
        {
            ARCH         => 'i386',
            THREAD       => '2',
            NAME         => 'Intel(R) Core(TM) i3 CPU M 350 @ 2.27GHz',
            CORE         => '2',
            MODEL        => '37',
            STEPPING     => '5',
            SPEED        => '2270',
            MANUFACTURER => 'Intel',
            FAMILYNUMBER => '6'
        }
    ]
);

my %alpha = (
    'linux-alpha-1' => [
        {
            SERIAL => 'JA30502089',
            ARCH   => 'Alpha',
            SPEED  => '1250',
            NAME   => undef
        }
    ]
);

my %sparc = (
    'linux-sparc-1' => [
        {
            ARCH => 'SPARC',
            NAME => 'TI UltraSparc IIIi (Jalapeno)'
        },
        {
            ARCH => 'SPARC',
            NAME => 'TI UltraSparc IIIi (Jalapeno)'
        }
    ]
);

my %arm = (
    'linux-armel-1' => [
        {
            ARCH  => 'ARM',
            NAME  => 'XScale-80219 rev 0 (v5l)'
        }
    ],
    'linux-armel-2' => [
        {
            ARCH  => 'ARM',
            NAME  => 'Feroceon 88FR131 rev 1 (v5l)'
        }
    ],
    'linux-armel-3' => [
        {
            ARCH  => 'ARM',
            NAME  => 'ARMv6-compatible processor rev 7 (v6l)'
        }
    ],
);

my %mips = (
    'linux-mips-1' => [
        {
            NAME => 'R4400SC V5.0 FPU V0.0',
            ARCH => 'MIPS'
        }
    ]
);

my %ppc = (
    'linux-ppc-1' => [
        {
            ARCH         => 'PowerPC',
            NAME         => '604r',
            MANUFACTURER => undef,
            SPEED        => undef
        }
    ],
    'linux-ppc-2' => [
        {
            ARCH         => 'PowerPC',
            NAME         => 'POWER4+ (gq)',
            MANUFACTURER => undef,
            SPEED        => '1452'
        },
        {
            ARCH         => 'PowerPC',
            NAME         => 'POWER4+ (gq)',
            MANUFACTURER => undef,
            SPEED        => '1452'
        }
    ],
    'linux-ppc-3' => [
        {
            ARCH         => 'PowerPC',
            NAME         => 'PPC970FX, altivec supported',
            MANUFACTURER => undef,
            SPEED        => '2700'
        },
        {
            ARCH         => 'PowerPC',
            NAME         => 'PPC970FX, altivec supported',
            MANUFACTURER => undef,
            SPEED        => '2700'
        }
    ]
);

plan tests =>
    (2 * scalar keys %alpha) +
    (2 * scalar keys %sparc) +
    (2 * scalar keys %arm)   +
    (2 * scalar keys %mips)  +
    (2 * scalar keys %ppc)   +
    (2 * scalar keys %i386)  +
    1;

my $inventory = FusionInventory::Test::Inventory->new();

foreach my $test (keys %i386) {
    my $cpuinfo   = "resources/linux/proc/cpuinfo/$test";
    my $dmidecode = "resources/generic/dmidecode/$test";
    my @cpus = FusionInventory::Agent::Task::Inventory::Linux::i386::CPU::_getCPUs(cpuinfo => $cpuinfo, dmidecode => $dmidecode);
    cmp_deeply(\@cpus, $i386{$test}, "cpus: ".$test);
    lives_ok {
        $inventory->addEntry(section => 'CPUS', entry => $_) foreach @cpus;
    } 'no unknown fields';
}

foreach my $test (keys %alpha) {
    my $file = "resources/linux/proc/cpuinfo/$test";
    my @cpus = FusionInventory::Agent::Task::Inventory::Linux::Alpha::CPU::_getCPUsFromProc(file => $file);
    cmp_deeply(\@cpus, $alpha{$test}, $test);
    lives_ok {
        $inventory->addEntry(section => 'CPUS', entry => $_) foreach @cpus;
    } 'no unknown fields';
}

foreach my $test (keys %sparc) {
    my $file = "resources/linux/proc/cpuinfo/$test";
    my @cpus = FusionInventory::Agent::Task::Inventory::Linux::SPARC::CPU::_getCPUsFromProc(file => $file);
    cmp_deeply(\@cpus, $sparc{$test}, $test);
    lives_ok {
        $inventory->addEntry(section => 'CPUS', entry => $_) foreach @cpus;
    } 'no unknown fields';
}

foreach my $test (keys %mips) {
    my $file = "resources/linux/proc/cpuinfo/$test";
    my @cpus = FusionInventory::Agent::Task::Inventory::Linux::MIPS::CPU::_getCPUsFromProc(file => $file);
    cmp_deeply(\@cpus, $mips{$test}, $test);
    lives_ok {
        $inventory->addEntry(section => 'CPUS', entry => $_) foreach @cpus;
    } 'no unknown fields';
}

foreach my $test (keys %arm) {
    my $file = "resources/linux/proc/cpuinfo/$test";
    my @cpus = FusionInventory::Agent::Task::Inventory::Linux::ARM::CPU::_getCPUsFromProc(file => $file);
    cmp_deeply(\@cpus, $arm{$test}, $test);
    lives_ok {
        $inventory->addEntry(section => 'CPUS', entry => $_) foreach @cpus;
    } 'no unknown fields';
}

foreach my $test (keys %ppc) {
    my $file = "resources/linux/proc/cpuinfo/$test";
    my @cpus = FusionInventory::Agent::Task::Inventory::Linux::PowerPC::CPU::_getCPUsFromProc(file => $file);
    cmp_deeply(\@cpus, $ppc{$test}, $test);
    lives_ok {
        $inventory->addEntry(section => 'CPUS', entry => $_) foreach @cpus;
    } 'no unknown fields';
}
